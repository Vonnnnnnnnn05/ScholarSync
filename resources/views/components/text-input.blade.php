@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-emerald-900/20 bg-white text-slate-900 focus:border-emerald-700 focus:ring-emerald-700 rounded-md shadow-sm']) }}>
