@php
    $review    = $shipment->review;
    $canRate   = ! $review
        && in_array($shipment->status, ['Delivered', 'Closed'], true)
        && auth()->id() === $shipment->user_id;
@endphp

@if ($review)
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="font-bold mb-2">Your rating</h3>
        <div class="text-2xl text-amber-500">
            {{ str_repeat('★', $review->rating) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $review->rating) }}</span>
        </div>
        @if ($review->comment)
            <p class="text-sm text-slate-600 mt-2">{{ $review->comment }}</p>
        @endif
    </div>
@elseif ($canRate)
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="font-bold mb-1">Rate this delivery</h3>
        <p class="text-sm text-slate-500 mb-4">How was your delivery? Choose 1 (poor) to 5 (excellent).</p>

        @if ($errors->has('rating') || $errors->has('comment'))
            <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">
                @foreach (array_merge($errors->get('rating'), $errors->get('comment')) as $message)
                    <div>{{ $message }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('reviews.store', $shipment) }}" class="space-y-3">
            @csrf
            <div class="flex gap-4">
                @foreach ([1, 2, 3, 4, 5] as $n)
                    <label class="flex items-center gap-1 cursor-pointer">
                        <input type="radio" name="rating" value="{{ $n }}" @checked((int) old('rating') === $n) required>
                        <span>{{ $n }} <span class="text-amber-500">★</span></span>
                    </label>
                @endforeach
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Comment (optional)</label>
                <textarea name="comment" rows="3" maxlength="1000" class="border rounded w-full px-3 py-2">{{ old('comment') }}</textarea>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Submit rating</button>
        </form>
    </div>
@endif