<?php

function controller_user($act, $d) {
    return match ($act) {
        'edit_window' => User::user_edit_window($d),
        'edit_update' => User::user_edit_update($d),
        'delete_window' => User::delete_edit_window($d),
        'delete_update' => User::user_delete_update($d),
        default => '',
    };
}
