<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\RoomBlock;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RoomBlockController
{
    /** Blocks one or more rooms for maintenance between two dates and times */
    public function store(Request $request)
    {
        $request->validate([
            'room_ids' => 'required|array|min:1',
            'room_ids.*' => 'integer|exists:rooms,id',
            'reason' => 'required|string|max:120',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ], [
            'room_ids.required' => 'Choose at least one room to block.',
        ]);

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);

        $blocked = [];
        $notFree = [];

        foreach (array_unique($request->room_ids) as $roomId) {
            $roomId = (int) $roomId;
            $label = str_pad($roomId, 2, '0', STR_PAD_LEFT);

            // The room must be free in that period: no booking, no reservation, no other block
            $roomIn = function ($column) use ($roomId) {
                return function ($q) use ($column, $roomId) {
                    $q->whereJsonContains($column, $roomId)->orWhereJsonContains($column, (string) $roomId);
                };
            };

            $hasBooking = Booking::whereNotIn('status', ['cancelled', 'checked_out'])
                ->where('check_in_datetime', '<', $end)
                ->where('check_out_datetime', '>', $start)
                ->where($roomIn('booked_rooms'))
                ->exists();

            $hasReservation = Reservation::whereNotIn('status', ['cancelled', 'checked_in'])
                ->where('reservation_start_date', '<', $end)
                ->where('reservation_end_date', '>', $start)
                ->where($roomIn('selected_rooms'))
                ->exists();

            $hasBlock = RoomBlock::where('room_id', $roomId)
                ->where('is_active', true)
                ->where('start_date', '<', $end)
                ->where('end_date', '>', $start)
                ->exists();

            if ($hasBooking || $hasReservation || $hasBlock) {
                $notFree[] = $label;
                continue;
            }

            RoomBlock::create([
                'room_id' => $roomId,
                'start_date' => $start,
                'end_date' => $end,
                'reason' => trim($request->reason),
                'is_active' => true,
            ]);

            $blocked[] = $label;
        }

        if (empty($blocked)) {
            return back()->withErrors(['room_ids' => 'None of the chosen rooms are free for these dates.'])->withInput();
        }

        $message = 'Room ' . implode(', ', $blocked) . ' blocked from ' . $start->format('M d, h:i A') . ' to ' . $end->format('M d, h:i A') . '.';

        if (!empty($notFree)) {
            $message .= ' Not blocked (not free): ' . implode(', ', $notFree) . '.';
        }

        return back()->with('success', $message);
    }

    /** Removes a block: the room is available again */
    public function destroy(int $id)
    {
        RoomBlock::findOrFail($id)->update(['is_active' => false]);

        return back()->with('success', 'Block removed. The room is available again.');
    }
}