<?php

/*
 * The hotel's own wording for validation messages.
 *
 * Laravel loads the messages it ships, then merges this file over the top, so
 * only the keys that need replacing belong here - every other message keeps the
 * framework's default wording and none of them need copying in.
 *
 * `phone` is the key propaganistas/laravel-phone fails with, and the package
 * ships no translation for it. Without this entry the guest is shown the literal
 * text "validation.phone", which tells them nothing about what to type instead.
 *
 * The wording has to work on three different forms - a guest booking a room, a
 * visitor writing through the contact form, and an administrator adding a member
 * of staff - so it says what a number is for rather than naming a field, and it
 * shows both shapes that are accepted: the way reception would say a Malawian
 * number aloud, and a full international one.
 */
return [
    'phone' => 'Enter a phone number we can reach you on, for example 0999 123 456. If you are outside Malawi, include your country code, for example +44 161 496 0000.',
];
