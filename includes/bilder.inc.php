<?php
function erzeugeQuadratischenAusschnitt(string $pfad, int $groesse, string $zielPfad, bool $mitWasserzeichen = false): bool {
    $bildinfos = getimagesize($pfad);
    if ($bildinfos === false) {
        return false;
    }

    $breite_orig = $bildinfos[0];
    $hoehe_orig = $bildinfos[1];
    $typ_orig = $bildinfos["mime"];
    $ar_orig = $breite_orig / $hoehe_orig;

    if ($ar_orig > 1) {
        // Querformatbild
        $x0 = intval(($breite_orig - $hoehe_orig) / 2);
        $y0 = 0;
        $breite0 = $hoehe_orig;
        $hoehe0 = $hoehe_orig;
    } else {
        // Hochformat oder Quadrat
        $x0 = 0;
        $y0 = intval(($hoehe_orig - $breite_orig) / 2);
        $breite0 = $breite_orig;
        $hoehe0 = $breite_orig;
    }

    $r1 = imagecreatetruecolor($groesse, $groesse); // quadratisches Bild
    if ($r1 === false) {
        return false;
    }

    switch ($typ_orig) {
        case "image/jpeg":
            $r0 = imagecreatefromjpeg($pfad);
            break;
        case "image/png":
            $r0 = imagecreatefrompng($pfad);
            break;
        case "image/gif":
            $r0 = imagecreatefromgif($pfad);
            break;
        case "image/webp":
            $r0 = imagecreatefromwebp($pfad);
            break;
        case "image/avif":
            $r0 = imagecreatefromavif($pfad);
            break;
        default:
            imagedestroy($r1);
            return false;
    }

    if ($r0 === false) {
        imagedestroy($r1);
        return false;
    }

    if (!imagecopyresampled($r1, $r0, 0, 0, $x0, $y0, $groesse, $groesse, $breite0, $hoehe0)) {
        imagedestroy($r0);
        imagedestroy($r1);
        return false;
    }

    if ($mitWasserzeichen) {
        $r1 = erzeugeWasserzeichen($r1);
        if ($r1 === false) {
            imagedestroy($r0);
            return false;
        }
    }

    $result = false;
    switch ($typ_orig) {
        case "image/jpeg":
            $result = imagejpeg($r1, $zielPfad);
            break;
        case "image/png":
            $result = imagepng($r1, $zielPfad);
            break;
        case "image/gif":
            $result = imagegif($r1, $zielPfad);
            break;
        case "image/webp":
            $result = imagewebp($r1, $zielPfad);
            break;
        case "image/avif":
            $result = imageavif($r1, $zielPfad);
            break;
    }

    imagedestroy($r0);
    imagedestroy($r1);

    return $result;
}
?>