<?php

/*
 * The hotel adds its own wording for validation messages in
 * lang/en/validation.php. Laravel loads the messages it ships first and merges
 * that file over the top, so the application's file only carries the keys it
 * replaces - currently just `phone`, which the phone package fails with and
 * ships no wording for.
 *
 * That merge is worth a test of its own. If it ever stopped happening, the
 * phone tests would still pass on their own wording while every other message in
 * the application quietly started showing its raw translation key - so a guest
 * mistyping their email would be told "validation.email".
 */

test('the wording the framework ships with still reaches the guest', function () {
    expect(__('validation.required', ['attribute' => 'first name']))
        ->toBe('The first name field is required.')
        ->and(__('validation.email', ['attribute' => 'email address']))
        ->toBe('The email address field must be a valid email address.');
});
