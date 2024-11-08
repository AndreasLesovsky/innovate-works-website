document.addEventListener("DOMContentLoaded", () => {

	// Menu Button Click Event
	document.querySelector("#menu-btn").addEventListener("click", (ev) => {
		const nav = document.querySelector("nav");
		nav.classList.toggle("menu-visible");

		const currentState = document.querySelector("#menu-btn").getAttribute("data-state");
		if (!currentState || currentState === "closed") {
			document.querySelector("#menu-btn").setAttribute("data-state", "opened");
			document.querySelector("#menu-btn").setAttribute("aria-expanded", "true");
		} else {
			document.querySelector("#menu-btn").setAttribute("data-state", "closed");
			document.querySelector("#menu-btn").setAttribute("aria-expanded", "false");
		}
	});

	// Header Scroll Functionality
	const header = document.querySelector('header');
	let lastScrollY = window.scrollY;
	const stickyPoint = 100;

	window.addEventListener('scroll', () => {
		const currentScrollY = window.scrollY;

		if (currentScrollY > stickyPoint) {
			if (currentScrollY < lastScrollY) {
				header.style.position = 'fixed';
				header.style.top = '0';
			} else {
				header.style.position = 'absolute';
				header.style.top = '-100px';
			}
		} else {
			header.style.position = 'absolute';
			header.style.top = '0';
		}

		lastScrollY = currentScrollY;
	});

	// Navigation Click Event Handlers
	function addClickListeners() {
		const li = document.querySelectorAll("nav li");
		li.forEach(item => item.addEventListener("click", handleClick));
	}

	function removeClickListeners() {
		const li = document.querySelectorAll("nav li");
		li.forEach(item => item.removeEventListener("click", handleClick));
	}

	function handleClick(ev) {
		ev.stopPropagation();
		const submenu = ev.currentTarget.lastElementChild;
		if (submenu && submenu.nodeName === "UL") {
			submenu.classList.toggle("menu-ul-ul-visible");

			// Set dynamic z-index
			if (submenu.classList.contains("menu-ul-ul-visible")) {
				const allSubmenus = document.querySelectorAll("nav ul ul");
				allSubmenus.forEach(sub => sub.style.zIndex = 1); // Reset all z-indexes
				submenu.style.zIndex = '9999'; // Set high z-index for current visible submenu
			} else {
				submenu.style.zIndex = '1'; // Reset to a lower z-index
			}
		}
	}

	// Media Query Listener
	const mediaQuery = window.matchMedia("(max-width: 80rem)");
	function handleMediaQueryChange(event) {
		if (event.matches) {
			addClickListeners();
		} else {
			removeClickListeners();
		}
	}
	handleMediaQueryChange(mediaQuery);
	mediaQuery.addEventListener("change", handleMediaQueryChange);

	// File Upload Handling
	document.getElementById('profilbild').addEventListener('change', function(event) {
		const file = event.target.files[0];
		if (file) {
			const fileName = file.name;
			document.getElementById('profilbild-name').textContent = fileName;

			const reader = new FileReader();
			reader.onload = function(e) {
				const preview = document.getElementById('profilbild-preview');
				preview.src = e.target.result;
				preview.style.display = 'block';
			};
			reader.readAsDataURL(file);
		} else {
			document.getElementById('profilbild-name').textContent = 'Keine Datei ausgewählt';
			document.getElementById('profilbild-preview').style.display = 'none';
		}
	});

	document.getElementById('dokumente').addEventListener('change', function(event) {
		const files = event.target.files;
		const fileCount = files.length;
		const fileNames = fileCount > 0 ? fileCount + ' Datei(en) ausgewählt:<br>' + Array.from(files).map(file => file.name).join('<br>') : 'Keine Dateien ausgewählt';
		const dokumenteNamen = document.getElementById('dokumente-namen');
		dokumenteNamen.innerHTML = fileNames;
		dokumenteNamen.style.display = fileCount > 0 ? 'block' : 'none';
	});

	// Clear Buttons
	document.getElementById('clear-profilbild').addEventListener('click', function() {
		const fileInput = document.getElementById('profilbild');
		fileInput.value = '';
		document.getElementById('profilbild-name').textContent = 'Keine Datei ausgewählt';
		document.getElementById('profilbild-preview').style.display = 'none';
	});

	document.getElementById('clear-dokumente').addEventListener('click', function() {
		const fileInput = document.getElementById('dokumente');
		fileInput.value = '';
		document.getElementById('dokumente-namen').textContent = 'Keine Dateien ausgewählt';
	});

	// Upload Buttons
	document.getElementById('upload-profilbild').addEventListener('click', function() {
		document.getElementById('profilbild').click();
	});

	document.getElementById('upload-pdf').addEventListener('click', function() {
		document.getElementById('dokumente').click();
	});

});