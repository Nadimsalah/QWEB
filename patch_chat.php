<?php
$content = file_get_contents('chat.php');

// Fix button CSS
$content = str_replace(
    'cursor: pointer; font-size: 16px; transition: all 0.2s;',
    'cursor: pointer; font-size: 16px; transition: all 0.2s; z-index: 10;',
    $content
);

// Fix button icon
$content = str_replace(
    '<button type="submit"><i class="fa-solid fa-search"></i></button>',
    '<button type="submit" style="z-index:10; position:relative;" onclick="this.closest(\'form\').submit();"><i class="fa-solid fa-magnifying-glass"></i></button>',
    $content
);

file_put_contents('chat.php', $content);
echo "Patched chat.php\n";
