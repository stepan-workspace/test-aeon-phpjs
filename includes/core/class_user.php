<?php

class User {

    // GENERAL

    public static function user_info($d) {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $phone = isset($d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : 0;
        // where
        if ($user_id) $where = "user_id='".$user_id."'";
        else if ($phone) $where = "phone='".$phone."'";
        else return [];
        // info
        $q = DB::query("SELECT user_id, phone, access FROM users WHERE ".$where." LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'access' => (int) $row['access']
            ];
        } else {
            return [
                'id' => 0,
                'access' => 0
            ];
        }
    }

    public static function user_info_by_id($user_id = 0) {
        // where
        $where = "user_id='".$user_id."'";
        // info
        $q = DB::query("SELECT * FROM users WHERE ".$where." LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'phone' => phone_formatting($row['phone']),
                'email' => $row['email'],
                'plot_id' => (string) $row['plot_id']
            ];
        } else {
            return [
                'id' => 0,
                'first_name' => '',
                'last_name' => '',
                'phone' => '',
                'email' => '',
                'plot_id' => '',
            ];
        }
    }

    public static function users_list_plots($number) {
        // vars
        $items = [];
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, email, phone
            FROM users WHERE plot_id LIKE '%".$number."%' ORDER BY user_id;") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $plot_ids = explode(',', $row['plot_id']);
            $val = false;
            foreach($plot_ids as $plot_id) if ($plot_id == $number) $val = true;
            if ($val) $items[] = [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'email' => $row['email'],
                'phone_str' => phone_formatting($row['phone'])
            ];
        }
        // output
        return $items;
    }

    public static function users_list($d = []) {
        // vars
        $f = isset($d['filter']) && is_array($d['filter']) ? $d['filter'] : [];
        $offset = isset($d['offset']) && is_numeric($d['offset']) ? $d['offset'] : 0;
        $where_formats = [
            'phone' => 'CAST(phone AS CHAR) LIKE \'%%%1$s%%\'',
            'name' => '(first_name LIKE \'%%%1$s%%\' OR last_name LIKE \'%%%1$s%%\')',
            'email' => 'email LIKE \'%%%1$s%%\''
        ];
        $limit = 20;
        $items = [];
        $url = [];
        // where
        $where = [];
        foreach ($f as $k => $v) {
            if (empty($v)) continue;
            $where[] = sprintf($where_formats[$k], $v);
            $url[] = "$k=$v";
        }
        $where = $where ? "WHERE ".implode(" AND ", $where) : "";
        // info
        $q = DB::query("SELECT * 
            FROM users ".$where." ORDER BY user_id LIMIT ".$offset.", ".$limit.";") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $items[] = [
                'id' => (int) $row['user_id'],
                'plot_id' => $row['plot_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'phone' => phone_formatting($row['phone']),
                'email' => $row['email'],
                'last_login' => $row['last_login']
            ];
        }
        // paginator
        $q = DB::query("SELECT count(*) FROM users ".$where.";");
        $count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
        $url = 'users?' . ($url ? implode('&', $url).'&' : '');
        paginator($count, $offset, $limit, $url, $paginator);
        // output
        return ['items' => $items, 'paginator' => $paginator];
    }

    public static function users_fetch($d = [], $offset = 0) {
        $info = User::users_list(['filter' => $d, 'offset' => $offset]);
        HTML::assign('users', $info['items']);
        return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $info['paginator']];
    }

    // ACTIONS

    public static function user_edit_window($d = []) {
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        HTML::assign('user', User::user_info_by_id($user_id));
        return ['html' => HTML::fetch('./partials/user_edit.html')];
    }

    public static function user_edit_update($d = []) {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $plot_id = isset($d['plot_id']) && is_string($d['plot_id']) ? $d['plot_id'] : '';
        $first_name = isset($d['first_name']) && trim($d['first_name']) ? trim($d['first_name']) : '';
        $last_name = isset($d['last_name']) && trim($d['last_name']) ? trim($d['last_name']) : '';
        $email = isset($d['email']) && trim($d['email']) ? strtolower(trim($d['email'])) : '';
        $phone = isset($d['phone']) && preg_replace('~\D+~', '', $d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : 0;
        $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;
        // update
        if ($user_id) {
            $set = [];
            $set[] = "plot_id='".$plot_id."'";
            $set[] = "first_name='".$first_name."'";
            $set[] = "last_name='".$last_name."'";
            $set[] = "email='".$email."'";
            $set[] = "phone=".$phone;
            $set[] = "updated='".Session::$ts."'";
            $set = implode(", ", $set);
            DB::query("UPDATE users SET ".$set." WHERE user_id='".$user_id."' LIMIT 1;") or die (DB::error());
        } else {
            DB::query("INSERT INTO users (
                plot_id,
                first_name,
                last_name,
                email,
                phone,
                updated
            ) VALUES (
                '".$plot_id."',
                '".$first_name."',
                '".$last_name."',
                '".$email."',
                ".$phone.",
                '".Session::$ts."'
            );") or die (DB::error());
        }
        // output
        return User::users_fetch(offset: $offset);
    }

    public static function delete_edit_window($d = []) {
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        HTML::assign('user', User::user_info_by_id($user_id));
        return ['html' => HTML::fetch('./partials/user_delete.html')];
    }

    public static function user_delete_update($d = []) {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;
        DB::query("DELETE FROM users WHERE user_id=".$user_id.";") or die (DB::error());
        // output
        return User::users_fetch(offset: $offset);
    }
}
