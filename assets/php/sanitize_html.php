<?php

function fg_sanitize_html(string $html): string
{
    $allowed_tags = '<a><abbr><address><article><aside><audio><b><bdi><bdo><blockquote><br><button><canvas><caption><cite><code><data><datalist><dd><del><details><dfn><div><dl><dt><em><figcaption><figure><footer><form><h1><h2><h3><h4><h5><h6><header><hr><i><iframe><img><input><ins><kbd><label><legend><li><main><mark><meter><nav><object><ol><optgroup><option><output><p><picture><pre><progress><q><rp><rt><ruby><s><samp><section><select><small><span><strong><sub><summary><sup><svg><table><tbody><td><template><textarea><tfoot><th><thead><time><tr><u><ul><var><video><wbr>';
    return trim(strip_tags($html, $allowed_tags));
}

