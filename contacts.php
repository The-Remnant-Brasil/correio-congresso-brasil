<?php

require "lib.php";

const CONTACTS_PATH = __DIR__ . "/contacts";

function store_contact(string $name, array $content): bool {
    $json = json_encode($content, JSON_PRETTY_PRINT);
    $name = sanitize_filename($name);
    $path = CONTACTS_PATH . "/$name.json";
    return $json && file_put_contents($path, $json) > 0;
}

function load_and_cache_all_contacts(): array {
    static $all_contacts_groups = [];

    try {
        if (empty($all_contacts_groups)) {
            $all_contacts_groups = load_all_files_contents_directory(CONTACTS_PATH);
            $all_contacts_groups = array_map(fn($val) => json_decode(json: $val, flags: JSON_THROW_ON_ERROR, associative: true), $all_contacts_groups);
        }
    } catch (JsonException) {
    }

    return $all_contacts_groups;
}

function get_contacts_by_group(array $groups): array|false {
    $all_contacts_groups = load_and_cache_all_contacts();
    $result = [];

    foreach ($groups as $group) {
        $group = sanitize_filename($group);

        if (!array_key_exists($group, $all_contacts_groups)) {
            return false;
        }
        foreach ($all_contacts_groups[$group] as $key => $val) {
            $result[$key] = $val;
        }
    }

    return $result;
}

function add_contacts(array $contacts): int {
    if (!file_exists(CONTACTS_PATH))
        mkdir(CONTACTS_PATH);

    $put = 0;

    foreach ($contacts as $name => $content) {
        $put += (int)store_contact($name, $content);
    }

    return $put;
}
