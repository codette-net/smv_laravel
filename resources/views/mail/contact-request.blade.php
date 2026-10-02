<x-mail::message>
# Nieuwe contactaanvraag

Er is een nieuw bericht verstuurd via het contactformulier op Salesenmarketingvacatures.nl.

**Naam:** {{ $contactName }}  
**E-mail:** {{ $contactEmail }}  
**Reden van contact:** {{ $purposeLabel }}  
@if ($company)
**Bedrijf:** {{ $company }}  
@endif
@if ($phone)
**Telefoonnummer:** {{ $phone }}  
@endif

**Bericht:**

<x-mail::panel>
{!! nl2br(e($messageBody)) !!}
</x-mail::panel>

Dit bericht is afkomstig van het publieke contactformulier.
</x-mail::message>
