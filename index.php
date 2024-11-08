<?php
require("includes/bilder.inc.php"); // Funktion "erzeugeQuadratischenAusschnitt"
require 'vendor/autoload.php'; // PHPMailer laden

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$msg = '';
$errors = [];

// reCAPTCHA-Schlüssel
$recaptchaSecret = ''; 

if (count($_POST) > 0) {
    // reCAPTCHA-Überprüfung
    if (isset($_POST['g-recaptcha-response'])) {
        $recaptchaResponse = $_POST['g-recaptcha-response'];
        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptchaResponse");
        $responseKeys = json_decode($response, true);

        if (intval($responseKeys["success"]) !== 1) {
            $errors[] = 'reCAPTCHA-Verifizierung fehlgeschlagen. Bitte erneut versuchen.';
        }
    } else {
        $errors[] = 'reCAPTCHA nicht bestätigt. Bitte erneut versuchen.';
    }

    // Wenn reCAPTCHA erfolgreich ist, führe die Speicherung durch
    if (empty($errors)) {
        $userDaten = $_POST;
        $userDaten['Bewerbungszeitpunkt'] = date('Y-m-d H:i:s');
        $VZUser = 'user/' . time() . '/';
        $VZPic = $VZUser . 'profilbild/';
        $VZDocs = $VZUser . 'dokumente/';

        if (!file_exists($VZUser) || !file_exists($VZPic) || !file_exists($VZDocs)) {
            if (!mkdir($VZUser, 0755, true) || !mkdir($VZPic, 0755, true) || !mkdir($VZDocs, 0755, true)) {
                $errors[] = 'Verzeichnisse konnten nicht erstellt werden.';
            }
        }

        $userDatenTxt = '';
        foreach ($userDaten as $key => $value) {
            $userDatenTxt .= $key . ': ' . htmlspecialchars($value) . PHP_EOL;
        }

        if (file_put_contents($VZUser . 'userdaten.txt', $userDatenTxt) === false) {
            $errors[] = 'Die Benutzerdaten konnten nicht gespeichert werden.';
        }

        if ($_FILES['profilbild']["error"] == 0) {
            $pfadPic = $_FILES['profilbild']['tmp_name'];
            $extPic = pathinfo($_FILES['profilbild']['name'], PATHINFO_EXTENSION);
            $zielPic = $VZPic . 'profilbild.' . $extPic;

            if (!erzeugeQuadratischenAusschnitt($pfadPic, 500, $zielPic)) {
                $errors[] = 'Das Profilbild konnte nicht verarbeitet werden.';
            }
        } else {
            $errors[] = 'Fehler beim Hochladen des Profilbilds.';
        }

        $docCount = 0;
        if (isset($_FILES['dokumente'])) {
            foreach ($_FILES['dokumente']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['dokumente']["error"][$key] == 0 && mime_content_type($tmpName) == 'application/pdf') {
                    $docName = basename($_FILES['dokumente']['name'][$key]);
                    if (move_uploaded_file($tmpName, $VZDocs . $docName)) {
                        $docCount++;
                    } else {
                        $errors[] = 'Das Dokument ' . $docName . ' konnte nicht hochgeladen werden.';
                    }
                } else if ($_FILES['dokumente']["error"][$key] != 4) { 
                    $errors[] = 'Fehler beim Hochladen des Dokuments ' . basename($_FILES['dokumente']['name'][$key]) . '.';
                }
            }
        }

        // Wenn keine Fehler aufgetreten sind, sende die Bestätigungsmail
        if (empty($errors)) {
            $mail = new PHPMailer(true);
            try {
                // Server-Einstellungen
                $mail->isSMTP();
                $mail->Host = ''; // SMTP-Server
                $mail->SMTPAuth = true;
                $mail->Username = ''; // SMTP-Benutzername
                $mail->Password = ''; // SMTP-Passwort
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
				$mail->CharSet = 'UTF-8';

                // Absender und Empfänger
                $mail->setFrom('', 'Innovate Works');
                $mail->addAddress($userDaten['email'], htmlspecialchars($userDaten['name']));

                // Inhalt der E-Mail
                $mail->isHTML(true);
                $mail->Subject = 'Bestätigung Ihrer Bewerbung';
                $mail->Body = 'Vielen Dank für Ihre Bewerbung. Ihre Daten wurden erfolgreich gespeichert.';

                $mail->send();
                $msg = '<div class="success">
                        <span>Das Speichern Ihrer Daten war erfolgreich. Sie haben ' . $docCount . ' Dokument(e) hochgeladen. Eine Bestätigungsmail wurde gesendet.</span>
                    </div>';
            } catch (Exception $e) {
                $errors[] = 'Die Bestätigungsmail konnte nicht gesendet werden. Mailer Error: ' . $mail->ErrorInfo;
            }
        } else {
            $msg = '<div class="error"><span>Fehler beim Speichern:</span><ul>';
            foreach ($errors as $error) {
                $msg .= '<li>' . $error . '</li>';
            }
            $msg .= '</ul></div>';
        }
    } else {
        $msg = '<div class="error"><span>Fehler bei reCAPTCHA:</span><ul>';
        foreach ($errors as $error) {
            $msg .= '<li>' . $error . '</li>';
        }
        $msg .= '</ul></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, user-scalable=yes">
	<title>Initiativbewerbung - InnovateWorks</title>
	<link rel="icon" href="media/icon.svg" sizes="any" type="image/svg+xml">
	<link rel="icon" href="media/icon.png" sizes="any" type="image/png">
	<link rel="stylesheet" href="css/importer.css">
	<link rel="stylesheet" href="css/reset.css">
	<link rel="stylesheet" href="css/config.css">
	<link rel="stylesheet" href="css/style.css">
	<link rel="stylesheet" href="css/media.css">
	<link rel="stylesheet" href="css/common.css">
	<script src="https://kit.fontawesome.com/7933e77e42.js" crossorigin="anonymous"></script>
	<script src="https://www.google.com/recaptcha/api.js" async defer></script>
	<script src="script.js"></script>
	<script>
        document.addEventListener("DOMContentLoaded", function() {
            <?php if ($msg): ?>
                // Scrollen zur Kommentar-Sektion
                const main = document.querySelector("main");
                if (main) {
                    main.scrollIntoView({
                        behavior: 'smooth',
						block: 'center'
                    });
                }
            <?php endif; ?>
        });
    </script>
</head>

<body>
	<header>
		<div class="inner-container">
			<a href="#">
				<svg width="60" height="60" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
					<polygon points="100,10 180,100 100,190 20,100" fill="var(--col-main)" transform="rotate(70, 100, 100)" />
					<polygon points="100,20 150,100 100,180 50,100" fill="var(--col-bg)" transform="rotate(70, 100, 100)" />
				</svg>
				<img src="media/innovateworks-logo.png" alt="innovateworks Logo">
				<img src="media/innovateworks-logo-light.png" alt="innovateworks Logo">
			</a>

			<nav>
				<ul>
					<li><a href="#">Home</a></li>
					<li>
						<a href="#">
							Unternehmen
							<span>
								<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
									<path d="M480-344 240-584l56-56 184 184 184-184 56 56-240 240Z"/>
								</svg>
							</span>
						</a>
						<ul>
							<li><a href="#">Über Uns</a></li>
							<li><a href="#">Globale Standorte</a></li>
							<li><a href="#">Lieferanten</a></li>
							<li><a href="#">Compliance</a></li>
							<li><a href="#">News</a></li>
							<li><a href="#">Auszeichnungen</a></li>
						</ul>
					</li>
					<li>
						<a href="#">
							Lösungen
							<span>
								<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
									<path d="M480-344 240-584l56-56 184 184 184-184 56 56-240 240Z"/>
								</svg>
							</span>
						</a>
						<ul>
							<li><a href="#">Schweiß- und Fügesysteme</a></li>
							<li><a href="#">Technische Falzlösungen</a></li>
							<li><a href="#">Automatisierung</a></li>
							<li><a href="#">Endmontage</a></li>
							<li><a href="#">Halterungen und Messgeräte</a></li>
						</ul>
					</li>
					<li>
						<a href="#">
							Dienstleistungen
							<span>
								<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
									<path d="M480-344 240-584l56-56 184 184 184-184 56 56-240 240Z"/>
								</svg>
							</span>
						</a>
						<ul>
							<li><a href="#">Service Überblick</a></li>
							<li><a href="#">Kundenspezifische Metallverarbeitung</a></li>
							<li><a href="#">Produktion Kleiner Stückzahlen</a></li>
							<li><a href="#">Additive Fertigung</a></li>
							<li><a href="#">Inspektion</a></li>
						</ul>
					</li>
					<li><a href="#">Karriere</a></li>
					<li><a href="#">Kontakt</a></li>
				</ul>

				<form id="header-form">
					<div class="search">
						<input type="search" class="search__input" aria-label="search" placeholder="Suche hier eingeben...">
						<button class="search__submit" aria-label="submit search">
							<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px">
								<path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z" />
							</svg>
						</button>
					</div>
					<select name="language">
						<option>Deutsch</option>
						<option>Englisch</option>
					</select>
				</form>
			</nav>

			<button type="button" id="menu-btn" aria-controls="menu" aria-expanded="false">
				<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
					<path d="M120-240v-80h720v80H120Zm0-200v-80h720v80H120Zm0-200v-80h720v80H120Z" />
				</svg>
			</button>
		</div>
	</header>

	<div class="banner"></div>

	<main>
		<h1>Initiativ<!-- <span>-<br></span> -->bewerbung</h1>
		<?php echo ($msg); ?>
		<p>
			Ihre Karriere beginnt hier! Bewerben Sie sich jetzt und werden Sie Teil unseres innovativen Teams.<br>Füllen Sie einfach das Formular aus und starten Sie mit uns durch.
		</p>

		<form id="upload-form" method="post" enctype="multipart/form-data">
			<fieldset>
				<legend>Persönliche Daten</legend>
				<p>Bitte füllen Sie die mit * gekennzeichneten Felder aus.</p>
				<div>
					Geschlecht:<br>
					<label>
						männlich
						<input type="radio" id="geschlechtM" name="geschlecht" value="m">
					</label>
					<label>
						weiblich
						<input type="radio" id="geschlechtW" name="geschlecht" value="w">
					</label>
					<label>
						divers
						<input type="radio" id="geschlechtW" name="geschlecht" value="d">
					</label>
				</div>
				<label>
					Vorname:*
					<input type="text" id="vorname" name="vorname" required>
				</label>
				<label>
					Nachname:*
					<input type="text" id="nachname" name="nachname" required>
				</label>

				<label>
					Geburtsdatum:
					<input type="date" id="geburtsdatum" name="geburtsdatum">
				</label>
				<label>
					Emailadresse:*
					<input type="email" id="email" name="email" required>
				</label>
				<label>
					Telefonnummer:*
					<input type="tel" id="telefon" name="telefon" required>
				</label>
				<label>
					Wie sind Sie auf uns aufmerksam geworden?:<br>
					<select name="known-by">
						<option>Arbeitsmarktservice</option>
						<option>karriere.at</option>
						<option>LinkedIn</option>
						<option>Personalvermittlung</option>
						<option>Zeitungsinserat</option>
						<option>Website</option>
						<option>Messe</option>
						<option>Sonstige</option>
					</select>
				</label>
				<br>
				<label>
					Sonstige Anmerkungen
					<textarea name="message"></textarea>
				</label>
			</fieldset>
			<fieldset>
				<legend>Anhänge</legend>
				<p>Wir bitten Sie zur Vervollständigung Ihrer Daten ein Motivationsschreiben, einen Lebenslauf, ggf. Zeugnisse und ein Foto hochzuladen, damit wir uns noch ein besseres Bild von Ihrer Bewerbung machen können.</p>

				<div class="custom-file-upload">
					<label for="profilbild" title="Klicken zum auswählen">
						Foto*
					</label>
					<button type="button" class="upload-button" id="upload-profilbild" title="Klicken zum auswählen">
						<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
							<path d="M440-320v-326L336-542l-56-58 200-200 200 200-56 58-104-104v326h-80ZM240-160q-33 0-56.5-23.5T160-240v-120h80v120h480v-120h80v120q0 33-23.5 56.5T720-160H240Z" />
						</svg>
					</button>
					<button type="button" class="clear-button" id="clear-profilbild" title="Auswahl löschen">
						<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
							<path d="M280-120q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM360-280h80v-360h-80v360Zm160 0h80v-360h-80v360ZM280-720v520-520Z" />
						</svg>
					</button>
					<input type="file" id="profilbild" name="profilbild" accept="image/*" required>
				</div>

				<div class="preview-container">
					<img id="profilbild-preview" src="#" alt="Bildvorschau">
					<div id="profilbild-name">Keine Datei ausgewählt</div>
				</div>

				<div class="custom-file-upload">
					<label for="dokumente" title="Klicken zum auswählen">
						PDF-Dokumente
					</label>
					<button type="button" class="upload-button" id="upload-pdf" title="Klicken zum auswählen">
						<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
							<path d="M440-320v-326L336-542l-56-58 200-200 200 200-56 58-104-104v326h-80ZM240-160q-33 0-56.5-23.5T160-240v-120h80v120h480v-120h80v120q0 33-23.5 56.5T720-160H240Z" />
						</svg>
					</button>
					<button type="button" class="clear-button" id="clear-dokumente" title="Auswahl löschen">
						<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="var(--col-font)">
							<path d="M280-120q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM360-280h80v-360h-80v360Zm160 0h80v-360h-80v360ZM280-720v520-520Z" />
						</svg>
					</button>
					<input type="file" id="dokumente" name="dokumente[]" accept="application/pdf" multiple>
				</div>

				<div class="preview-container">
					<div id="dokumente-namen">Keine Dateien ausgewählt</div>
				</div>

				<p>Bitte beachten Sie dass nur das PDF Format für Dokumente unterstützt wird und die Gesamtgröße aller Dateien 50 MB nicht überschreiten darf.</p>
			</fieldset>
			<fieldset>
				<legend>Datenschutzerklärung</legend>
				<p>Ihre Daten werden streng vertraulich behandelt und nicht an Dritte weitergegeben.</p>
				<label class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-xs-12">
					<input type="checkbox" name="AGB" value="akzeptiert" required>
					Ich habe die <a href="#">Datenschutzbestimmungen</a> gelesen und akzeptiere sie.*
				</label>
				<div class="g-recaptcha" data-sitekey=""></div>
				<input type="submit" value="Bewerben" class="hvr-push">
				<p>Mit dem Absenden Ihrer Bewerbung bestätigen Sie die Richtigkeit Ihrer Angaben.</p>
			</fieldset>
		</form>
	</main>
	<footer>
		<div class="inner-container">
			<div>
				<h2>Kontaktieren Sie Uns</h2>
				<ul>
					<li><a href="#">Kontaktformular</a></li>
					<li><a href="#">Globale Standorte</a></li>
				</ul>
			</div>
			<div>
				<h2>Information</h2>
				<ul>
					<li><a href="#">Nutzungsbedingungen</a></li>
					<li><a href="#">Datenschutzerklärung</a></li>
					<li><a href="#">Cookie-Richtlinie</a></li>
					<li><a href="#">Social-Media-Richtlinie</a></li>
					<li><a href="#">Impressum</a></li>
				</ul>
			</div>
			<div>
				<h2>Lassen Sie uns auf Social Media in Verbindung treten</h2>
				<ul>
					<li><a href="#"><i class="fa-brands fa-linkedin-in"></i></a></li>
					<li><a href="#"><i class="fa-brands fa-facebook-f"></i></a></li>
					<li><a href="#"><i class="fa-brands fa-x-twitter"></i></a></li>
					<li><a href="#"><i class="fa-brands fa-youtube"></i></a></li>
					<li><a href="#"><i class="fa-brands fa-instagram"></i></a></li>
				</ul>
			</div>
			<div>
				<h2>Unternehmenszentrale</h2>
				<p>
					Musterstraße 58<br>
					4020 Linz Österreich<br>
					Telefon +43 538 984 8787
				</p>
			</div>
			<address>
				©INNOVATE WORKS 2024. Alle Rechte vorbehalten.
			</address>
		</div>
	</footer>
	<script>
		(function() {
			const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)').matches;
			const recaptchaElement = document.querySelector('.g-recaptcha');

			if (prefersDarkScheme) {
				recaptchaElement.setAttribute('data-theme', 'dark');
			} else {
				recaptchaElement.setAttribute('data-theme', 'light');
			}
		})();
	</script>
</body>

</html>
