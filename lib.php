<?php

function load_all_files_contents_directory(string $dir): array {
    $file_info = new SplFileInfo($dir);
    if (!$file_info->isDir())
        return [];

    $data = [];
    $filesystem = new FilesystemIterator($dir); 

    foreach ($filesystem as $file) {
        $name = str_replace(".json", "", $file->getFilename());
        $data[$name] = file_get_contents($file->getPathname());
    }
    
    return $data;
}

function sanitize_filename(string $filename): string {
    return preg_replace("[^a-zA-Z0-9_]", "", $filename);
}

function get_host($from) {
    static $providers = [
        '@outlook' => 'smtp-email.outlook.com',
        '@gmail.com$' => 'smtp.gmail.com',
    ];

    foreach ($providers as $regex => $server_name) {
        if (preg_match("/$regex/", $from)) {
            return $server_name;
        }
    }

    throw new Exception("Servidor para email $from não suportado.");
}
