<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEventGuestRegistrationRequest;
use App\Http\Requests\Api\StoreEventRegistrationRequest;
use App\Http\Requests\Api\StoreEventRequest;
use App\Http\Requests\Api\UpdateEventRequest;
use App\Http\Resources\Api\EventListResource;
use App\Http\Resources\Api\EventRegistrationResource;
use App\Http\Resources\Api\EventResource;
use App\Models\Event;
use App\Models\EventPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventController extends Controller
{
    /**
     * Display a listing of events (public).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Event::query()->withCount('registrations');

        $status = $request->query('status');
        if ($status && in_array($status, ['open', 'closed'], true)) {
            $query->where('status', $status);
        }

        if ($request->boolean('upcoming')) {
            $query->open()->upcoming();
        }

        $query->orderBy('event_at', 'desc');

        $events = $query->get();

        return response()->json([
            'data' => EventListResource::collection($events),
        ]);
    }

    /**
     * Display the specified event (public for non-draft; super_admin can see draft).
     */
    public function show(Request $request, Event $event): JsonResponse
    {
        if ($event->status === EventStatus::Draft) {
            if (! $request->user() || ! $request->user()->isSuperAdmin()) {
                abort(404);
            }
        }

        $event->load('photos')->loadCount('registrations');

        if ($request->user()) {
            $event->setAttribute('is_registered', $event->registrations()
                ->where('user_id', $request->user()->id)
                ->exists());
        }

        return response()->json([
            'data' => new EventResource($event),
        ]);
    }

    /**
     * Register the current user for the event (member only).
     */
    public function register(StoreEventRegistrationRequest $request, Event $event): JsonResponse
    {
        if (! $request->user()) {
            abort(401, 'Unauthenticated.');
        }

        if (! $request->user()->member_id) {
            abort(403, 'Only approved members can register for events.');
        }

        if ($event->status !== EventStatus::Open) {
            abort(422, 'Event is not open for registration.');
        }

        if (! $event->isRegistrationOpen()) {
            abort(422, $event->registrationClosedMessage());
        }

        $exists = $event->registrations()->where('user_id', $request->user()->id)->exists();
        if ($exists) {
            abort(422, 'Already registered for this event.');
        }

        $user = $request->user();
        $user->load('memberProfile');
        $data = $request->validated();

        $paymentPath = null;
        if ($request->hasFile('payment_document')) {
            $file = $request->file('payment_document');
            $filename = 'payment_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $paymentPath = $file->storeAs('events/registrations/'.$event->id, $filename, 'public');
        }

        $guestCount = (int) ($data['guest_count'] ?? 0);
        $eventFee = $event->fee !== null ? (float) $event->fee : null;
        $participantFee = isset($data['participant_fee']) ? (float) $data['participant_fee'] : $eventFee;
        $totalFees = isset($data['total_fees']) ? (float) $data['total_fees'] : ($eventFee !== null ? $eventFee * (1 + $guestCount) : null);

        $event->registrations()->create([
            'user_id' => $user->id,
            'registered_at' => now(),
            'name' => $data['name'] ?? $user->name,
            'phone' => $data['phone'] ?? $user->phone,
            'address' => $data['address'] ?? $user->memberProfile?->present_address,
            'ssc_jsc' => $data['ssc_jsc'] ?? ($user->memberProfile?->ssc_year ? 'SSC '.$user->memberProfile->ssc_year : null),
            'notes' => $data['notes'] ?? null,
            'guest_count' => $guestCount,
            'guest_details' => $data['guest_details'] ?? null,
            'participant_fee' => $participantFee,
            'total_fees' => $totalFees,
            'payment_document_path' => $paymentPath,
        ]);

        return response()->json(['message' => 'Registered successfully.'], 201);
    }

    /**
     * Unregister the current user from the event.
     */
    public function unregister(Request $request, Event $event): JsonResponse
    {
        if (! $request->user()) {
            abort(401, 'Unauthenticated.');
        }

        $event->registrations()->where('user_id', $request->user()->id)->delete();

        return response()->json(null, 204);
    }

    /**
     * Register a guest for the event (public, no auth).
     */
    public function registerGuest(StoreEventGuestRegistrationRequest $request, Event $event): JsonResponse
    {
        if ($event->status !== EventStatus::Open) {
            abort(422, 'Event is not open for registration.');
        }

        if (! $event->isRegistrationOpen()) {
            abort(422, $event->registrationClosedMessage());
        }

        $data = $request->validated();

        $paymentPath = null;
        if ($request->hasFile('payment_document')) {
            $file = $request->file('payment_document');
            $filename = 'payment_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $paymentPath = $file->storeAs('events/registrations/'.$event->id, $filename, 'public');
        }

        $guestCount = (int) ($data['guest_count'] ?? 0);
        $eventFee = $event->fee !== null ? (float) $event->fee : null;
        $participantFee = isset($data['participant_fee']) ? (float) $data['participant_fee'] : $eventFee;
        $totalFees = isset($data['total_fees']) ? (float) $data['total_fees'] : ($eventFee !== null ? $eventFee * (1 + $guestCount) : null);

        $event->registrations()->create([
            'user_id' => null,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'ssc_jsc' => $data['ssc_jsc'] ?? null,
            'registered_at' => now(),
            'notes' => $data['notes'] ?? null,
            'guest_count' => $guestCount,
            'guest_details' => $data['guest_details'] ?? null,
            'participant_fee' => $participantFee,
            'total_fees' => $totalFees,
            'payment_document_path' => $paymentPath,
        ]);

        return response()->json(['message' => 'Registered successfully.'], 201);
    }

    /**
     * Store a newly created event (super_admin only).
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        unset($data['cover_photo']);
        if ($request->hasFile('cover_photo')) {
            $file = $request->file('cover_photo');
            $filename = 'cover_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['cover_photo'] = $file->storeAs('events/covers', $filename, 'public');
        }
        $event = Event::create($data);

        $event->loadCount('registrations');

        return (new EventResource($event))->response()->setStatusCode(201);
    }

    /**
     * Update the specified event (super_admin only).
     */
    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        unset($data['cover_photo'], $data['photos']);

        if ($request->hasFile('cover_photo')) {
            if ($event->cover_photo) {
                Storage::disk('public')->delete($event->cover_photo);
            }
            $file = $request->file('cover_photo');
            $filename = 'cover_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['cover_photo'] = $file->storeAs('events/covers', $filename, 'public');
        }

        if ($request->hasFile('photos')) {
            $sortOrder = 0;
            foreach ($request->file('photos') as $photo) {
                $filename = 'photo_'.Str::random(20).'.'.$photo->getClientOriginalExtension();
                $path = $photo->storeAs('events/'.$event->id.'/gallery', $filename, 'public');
                $event->photos()->create([
                    'path' => $path,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        if ($request->has('fee')) {
            $data['fee'] = $request->input('fee') === '' || $request->input('fee') === null
                ? null
                : (float) $request->input('fee');
        }

        $event->update($data);

        $event->load('photos')->loadCount('registrations');

        return response()->json([
            'data' => new EventResource($event),
        ]);
    }

    /**
     * Remove a photo from the event gallery (super_admin only).
     */
    public function destroyPhoto(Event $event, EventPhoto $eventPhoto): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        if ($eventPhoto->event_id !== $event->id) {
            abort(404);
        }

        Storage::disk('public')->delete($eventPhoto->path);
        $eventPhoto->delete();

        return response()->json(null, 204);
    }

    /**
     * Remove the specified event (super_admin only).
     */
    public function destroy(Event $event): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        if ($event->cover_photo) {
            Storage::disk('public')->delete($event->cover_photo);
        }
        foreach ($event->photos as $photo) {
            Storage::disk('public')->delete($photo->path);
        }
        $event->delete();

        return response()->json(null, 204);
    }

    /**
     * List registrations for the event (super_admin only).
     */
    public function registrations(Request $request, Event $event): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $registrations = $event->registrations()->with('user')->orderBy('registered_at', 'desc')->get();

        return response()->json([
            'data' => EventRegistrationResource::collection($registrations),
        ]);
    }
}
