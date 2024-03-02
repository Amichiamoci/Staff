$(document).ready(function() {

/* 01) Page scroll ---------------------------------------------------------- */

$("a[href^='#']").on("click", function() {
	var l_iOffset = 80;
    var l_sLink   = $(this).attr("href");

    if (l_sLink == "#top")
    {
    	l_iOffset = 0;
    }

    $("html, body").animate( {
        scrollTop: $(l_sLink).offset().top - l_iOffset
    }, 1000);

	$(".hamburger").removeClass("open");
    $(".navbar").removeClass("show");
    $("body").removeClass("no-scroll");

    return false;
});

/* 02) Scroll top button ---------------------------------------------------- */

$(window).scroll(function () {
	if ($(this).scrollTop() > 0)
	{
		$(".scroll-top").addClass("show");
		$("header").addClass("fill-bg");
	}
	else
	{
		$(".scroll-top").removeClass("show");
		$("header").removeClass("fill-bg");
	}
});

/* 03) Show info popup ------------------------------------------------------ */

$(".info-button").click(function() {
	let l_sID = $(this).attr("id");

	l_sID = l_sID.substring(l_sID.indexOf("-") + 1);

	let l_sPopupID = "#popup-" + l_sID;

	$(l_sPopupID).addClass("show");
});

/* 04) Close info popup ----------------------------------------------------- */

$(".close-button").click(function() {
	$(".popup-box").removeClass("show");
});

}); // ./document ready


/**
 * sends a request to the specified url from a form. this will change the window location.
 * @param {string} path the path to send the post request to
 * @param {object} params the parameters to add to the url
 * @param {string} method the method to use on the form
 */
function post(path, params, method='post') {

	// The rest of this code assumes you are not using a library.
	// It can be made less verbose if you use one.
	const form = document.createElement('form');
	form.hidden = true;
	form.method = method;
	form.action = path;
  
	for (const key in params) {
	  if (params.hasOwnProperty(key)) {
		const hiddenField = document.createElement('input');
		hiddenField.type = 'hidden';
		hiddenField.name = key;
		hiddenField.value = params[key];
  
		form.appendChild(hiddenField);
	  }
	}
  
	document.body.appendChild(form);
	form.submit();
}

/**
 * sends a request to the specified url from a form. this will change the window location.
 * @param {string} path the path to send the post request to
 * @param {object} params the parameters to add to the url
 * @param {string} method the method to use on the form
 * @returns {Promise<Response>}
 */
async function post_async(path, params = null, method = 'post')
{
	if (params)
	{
		const form_data = new FormData();
		for (const [name, value] of Object.entries(params))
		{
			form_data.append(name, value);
		}
		return await fetch(path, {
			method: method,
			body: form_data
		});
	}
	return await fetch(path, {
		method: method
	});
}

/**
 * sends a request to the specified url from a form. this will change the window location.
 * @param {string} path the path to send the post request to
 * @param {object} params the parameters to add to the url
 * @param {string} method the method to use on the form
 * @returns {object}
 */
async function post_async_json(path, params = null, method = 'post')
{
	try {
		const response = await post_async(path, params, method);
		if (!response.ok)
		{
			return {};
		}
		return await response.json();
	} catch (err) {
		console.warn(err);
		return {};
	}
}

/**
 * sends a request to the specified url from a form. this will change the window location.
 * @param {string} path the path to send the post request to
 * @param {object} params the parameters to add to the url
 * @param {string} method the method to use on the form
 * @returns {string}
 */
async function post_async_text(path, params = null, method = 'post')
{
	try {
		const response = await post_async(path, params, method);
		if (!response.ok)
		{
			return '';
		}
		return await response.text();
	} catch (err) {
		console.warn(err);
		return '';
	}
}

/**
 * 
 * @param {HTMLElement} elem 
 */
async function share_link(elem)
{
	const obj = {
		text: elem.getAttribute("data-share-text"),
		title: elem.getAttribute("data-share-title"),
		url: elem.href
	};
	if (!navigator.canShare(obj))
		return;
	await navigator.share(obj);
};

[...document.getElementsByClassName('share')].forEach(elem => elem.onclick = evt => {
	evt.preventDefault();
	share_link(elem);
});
[...document.querySelectorAll('[data-load-url]')].forEach(elem => {
	const url = elem.getAttribute('data-load-url');
	fetch(url).then(
		response => response.text().then(
			text => elem.innerText = text).catch(err => elem.innerText = String(err))
			).catch(err => elem.innerText = String(err));
});


async function SyncMatchDate(match)
{
	const new_date_elem = document.getElementById('date-' + match);
	if (!new_date_elem)
		return;
	const new_date = new_date_elem.value;
	new_date_elem.disabled = true;
	const obj = await post_async_json(
		'/admin/staff/tornei/partita.php',
		{
			id: match,
			data: new_date
		});
	new_date_elem.disabled = false;
	console.log(obj);
	return obj.message === 'ok';
}

async function SyncMatchTime(match)
{
	const new_time_elem = document.getElementById('time-' + match);
	if (!new_time_elem)
		return;
	const new_time = new_time_elem.value;
	new_time_elem.disabled = true;
	const obj = await post_async_json(
		'/admin/staff/tornei/partita.php',
		{
			id: match,
			ora: new_time
		});
	new_time_elem.disabled = false;
	console.log(obj);
	return obj.message === 'ok';
}

async function SyncMatchWhere(match)
{
	const new_where_elem = document.getElementById('where-' + match);
	if (!new_where_elem)
		return;
	const new_where = new_where_elem.value;
	new_where_elem.disabled = true;
	const obj = await post_async_json(
		'/admin/staff/tornei/partita.php',
		{
			id: match,
			campo: new_where
		});
	new_where_elem.disabled = false;
	console.log(obj);
	return obj.message === 'ok';
}

async function AddScore(match)
{
	const btn = document.getElementById('add-score-btn-' + match);
	const tr = btn.parentElement.parentElement;
	if (!btn || !tr)
		return;
	btn.disabled = true;
	const obj = await post_async_json(
		'/admin/staff/tornei/punteggio.php',
		{
			id: match,
			add_score: 'yes'
		});
	btn.disabled = false;
	if (!obj.id)
	{
		console.log(obj);
		return false;
	}
	const id = obj.id;
	
	const td = document.createElement('td');
	td.setAttribute('data-label', 'Match - Nuovo');
	td.setAttribute('data-match', id);

	const input = document.createElement('input');
	input.placeholder = '1 - 1';
	input.pattern = '[0-9]+\\s{0,}-\\s{0,}[0-9]+';
	input.type = 'text';
	input.id = 'match-' + id;
	input.oninput = () => SyncScore(id);

	const remove_btn = document.createElement('button');
	remove_btn.type = 'button';
	remove_btn.title = 'Rimuovi Match';
	remove_btn.style.color = 'var(--cl-red)';
	remove_btn.onclick = () => RemoveScore(id);
	remove_btn.innerHTML = 'X';

	td.appendChild(input);
	td.appendChild(remove_btn);

	tr.insertBefore(td, btn.parentElement)
	return true;
}

async function SyncScore(score)
{
	console.log('SyncScore(' + String(score) + ')');
	/**
	 * @type {HTMLInputElement}
	 */
	const input = document.getElementById('match-' + score);
	if (!input)
	{
		return;
	}
	const trimmed = input.value.trim();
	const regex = new RegExp(input.pattern)
	if (!regex.test(trimmed))
	{
		console.log('Regex not passed!');
		return;
	}
	input.disabled = true;
	const obj = await post_async_json(
		'/admin/staff/tornei/punteggio.php',
		{
			id: score,
			update_score: trimmed
		});
	input.disabled = false;
	return obj.message === 'ok';
}

async function RemoveScore(score)
{
	/**
	 * @type {HTMLInputElement}
	 */
	const input = document.getElementById('match-' + score);
	if (!input)
	{
		return;
	}
	input.disabled = true;
	const obj = await post_async_json(
		'/admin/staff/tornei/punteggio.php',
		{
			id: score,
			remove_score: 'yes'
		});
	const td = input.parentElement;
	const tr = td.parentElement;
	tr.removeChild(td);
	return obj.message === 'ok';
}

async function TestLinkAndReload(url)
{
	if (!url)
		return;
	const resp = await fetch(url);
	if (resp.ok)
	{
		window.location.reload();
	}
}