@props(['name' => 'circle'])
@php($directional=in_array($name,['arrow-right','arrow-left','arrow-up-right','arrow-up-left','arrow-down-right','arrow-down-left','chevron-right','chevron-left','chevrons-right','chevrons-left','external-link','log-in','log-out']))
@svg('lucide-'.$name, trim($attributes->get('class', 'icon').($directional ? ' rtl-directional' : '')), ['aria-hidden'=>'true', 'focusable'=>'false'])
