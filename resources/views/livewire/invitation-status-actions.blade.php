<div
    class="absolute right-0 bottom-0 left-0 mt-24 pl-4 pt-4 pb-4 flex flex-col md:flex-row gap-4 items-center border-t-2 border-slate-500 ">
    <div class="flex-grow flex gap-4 w-full">
        <button wire:click.prevent="accept"
            class="px-4 bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded w-full">{{ __('calendar.accept_invitation') }}
</button>
<button wire:click.prevent="decline"
        class="bg-red-500 hover:bg-red-600  text-white py-2 px-4 rounded w-full">{{ __('calendar.decline_invitation') }}
</button>
<button wire:click.prevent="missed"
        class="bg-gray-700 hover:bg-gray-800 text-white py-2 px-4 rounded w-full">{{ __('calendar.missed_invitation') }}
</button>
</div>
<input type="text" wire:model="note" placeholder="{{ __('calendar.add_optional_note') }}"
       class="border border-gray-300 rounded p-2 w-full text-black mr-4">
</div>
