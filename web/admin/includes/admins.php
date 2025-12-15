<?
$query = "SELECT COUNT(id) as cprod FROM admins";

$result = mysqli_query($link, $query) or die("SQL query error: " . basename(__FILE__) . " <b>$query</b><br>at line: " . __LINE__);

$row = mysqli_fetch_array($result, MYSQLI_ASSOC);

$count = $row['cprod'];

$cc = ceil($count / 250);

$busc = '';
?>

<div id="debug"></div>

<!-- Begin Page Content -->

<div class="container-fluid">

	<h1 class="h3 mb-2 text-gray-800">Admins</h1>

	<div class="card shadow mb-4">

		<div class="card-body">

			<h6 class="m-0 font-weight-bold text-primary py-3">Admins list:

				<?= $count ?>

			</h6>

			<div class="pager">

				<? for ($i = 0; $i < $cc; $i++) { ?>

					<div class="pgr_box" id="pager_<?= $i ?>" onclick="admin_list(<?= $i ?>, '<?=$busc?>')">

						<?= $i + 1 ?>

					</div>

				<? } ?>

			</div>

			<div class="uploadload" id="uploadload"></div>

            <div class="addnew_ico" onclick="admin_add_open()">
                <img id="plusIcon" src="img/plus.png" class="icon-size">
            </div>

			<div class="addnew">

            <div class="adm_add_tit">Login:</div>
				<div class="adm_add_txt"><input class="add_input" type="text" id="login"></div>

				<div class="adm_add_tit">Password:</div>
				<div class="adm_add_txt"><input class="add_input" type="text" id="password"></div>

                <div style="grid-column: 1/-1; text-align:right; font-size:30px;" onclick="admin_create()">
                    <img id="plusIcon" src="img/save.png" class="icon-size">
                </div>

			</div>

			<div class="table-responsive admins" id="admin_list"></div>

			<div class="row">

				<div class="col-sm-12 col-md-5">

					<div class="dataTables_info" id="dataTable_info" role="status" aria-live="polite"></div>

				</div>

			</div>

		</div>

	</div>

<script language="javascript" type="text/javascript">

function admin_add_open() {
    let st = document.querySelector('.addnew');
    let plusIcon = document.getElementById('plusIcon');

    if (st.style.display === 'grid') {
        st.style.display = 'none';
        plusIcon.src = "img/plus.png";
    } else {
        st.style.display = 'grid';
        plusIcon.src = "img/close.png";
    }
}
function admin_list(pg, busc) {
	document.getElementById('admin_list').innerHTML = '<img class="loading" src="img/loading_modern.gif">';

	fetch('includes/admins_list_js.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify({ pg, busc })
	})
	.then(response => response.json()) 
	.then(data => {
		document.getElementById('debug').innerHTML = data.debug || '';
		document.getElementById('admin_list').innerHTML = data.res;
		document.getElementById('dataTable_info').innerHTML = 'Showing: ' + data.cant + ' admins';

		for (let i = 0; i < <?= $cc ?>; i++) {
			let pageElem = document.getElementById('pager_' + i);
			if (pageElem) pageElem.style.backgroundColor = "#CCC";
		}

		let activePageElem = document.getElementById('pager_' + pg);
		if (activePageElem) activePageElem.style.backgroundColor = "#999";
	})
	.catch(error => {
		console.error('Error en la petición:', error);
		document.getElementById('admin_list').innerHTML = '<p style="color:red;">Failed to get admins list</p>';
	});
}
function admin_get_edit_form(id) {
    const editBox = document.getElementById('adm_list_edit_box' + id);
	const editIcon = document.getElementById('edit_icon_' + id);

    if (editBox.style.display === "grid") {
        editBox.style.display = "none";
        if (editIcon) editIcon.src = "img/edit.png";
        return;
    }

    fetch('includes/admins_edit_form_js.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok === 1) {
            document.getElementById('debug').innerHTML = data.debug || '';
            editBox.innerHTML = data.res;
            editBox.style.display = "grid";

            if (editIcon) editIcon.src = "img/close.png";
        } else {
            console.error("Failed to get admin edit form:", data.err);
            document.getElementById('debug').innerHTML = `<p style="color:red;">Error: ${data.err}</p>`;
        }
    })
    .catch(error => {
        console.error("Connection error:", error);
        document.getElementById('debug').innerHTML = `<p style="color:red;">Failed to get admin edit form</p>`;
    });
}
function admin_edit_save(id) {
    let data = {
        id:		id,
        login:		document.getElementById('login' + id)?.value		|| '',
        password:	document.getElementById('password' + id)?.value	|| ''
    };

    fetch('includes/admins_edit_js.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(responseData => {
        console.log(responseData);

        if (responseData.ok === 1) {
			const editIcon = document.getElementById('edit_icon_' + id);
            if (editIcon) editIcon.src = "img/edit.png";

            document.getElementById('adm_list_edit_box' + id).style.display = "none";

            document.getElementById('c0_' + id).innerHTML = data.id;
            document.getElementById('c1_' + id).innerHTML = data.login;
            document.getElementById('c2_' + id).innerHTML = data.password;
        } else {
            console.error("Failed to save:", responseData.err);
        }
    })
    .catch(error => {
        console.error("Connection error:", error);
    });
}
function admin_del(id) {
	if (!confirm("Are you sure to delete this data?\nThere is no way to recover the deleted data")) {
        return;
    }

	fetch('includes/admins_del_js.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify({ id: id })
	})
	.then(response => response.json()) 
	.then(data => {
		if (data.ok === 1) {
			document.querySelectorAll(`[id^=c][id$=_${id}]`).forEach(el => el.style.display = "none");

            let countElem = document.getElementById('dataTable_info');
            if (countElem) {
                let countText = countElem.innerText;
                let currentCount = parseInt(countText.split(':')[1].trim());
                if (currentCount > 0) {
                    countElem.innerHTML = 'Showing: ' + (currentCount - 1) + ' admins';
                }
            }
		} else {
			console.error("Failed to delete:", data.err);
		}
	})
	.catch(error => {
		console.error("Connection error:", error);
	});
}
function admin_create() {
    loginEl = document.getElementById('login');
    passwordEl = document.getElementById('password');

    let data = {
        login:		loginEl?.value		|| '',
        password:	passwordEl?.value	|| ''
    };

    fetch('includes/admins_create_js.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(responseData => {
        console.log(responseData);
        if (responseData.ok === 1) {
            document.querySelector('.addnew').style.display = 'none';
            document.getElementById('plusIcon').src = "img/plus.png";

            loginEl.value = '';
            passwordEl.value = '';

            admin_list(0, '');
        } else {
            console.error("Failed to save:", responseData.err);
        }
    })
    .catch(error => {
        console.error("Connection error:", error);
    });
}
function admin_list_by_filter() {
	let busc = document.getElementById('busc_texto').value;
	admin_list(0,busc);
}

admin_list(0,'');

</script>