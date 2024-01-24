<?php

function controller_users() {
    // vars
    $offset = isset($_GET['offset']) ? flt_input($_GET['offset']) : 0;
    $filter = array_fill_keys(['phone', 'name', 'email'], '');
    array_walk($filter, fn(&$v, $k): string => $v = isset($_GET[$k]) ? flt_input($_GET[$k]) : $v);
    // info
    $users = User::users_list(['offset' => $offset, 'filter' => $filter]);
    // output
    HTML::assign('users', $users['items']);
    HTML::assign('paginator', $users['paginator']);
    HTML::assign('filter', $filter);
    HTML::assign('offset', $offset);
    HTML::assign('section', 'users.html');
    HTML::assign('main_content', 'home.html');
}
