<?
$query = "SELECT COUNT(id) as cprod FROM tg_bot_actions";

$result = mysqli_query($link, $query) or die("SQL query error: " . basename(__FILE__) . " <b>$query</b><br>at line: " . __LINE__);

$row = mysqli_fetch_array($result, MYSQLI_ASSOC);

$count = $row['cprod'];

$cc = ceil($count / 250);

$busc = '';
?>

<div id="debug"></div>

<!-- Begin Page Content -->

<div class="container-fluid">

	<h1 class="h3 mb-2 text-gray-800">TG bot actions</h1>

	<div class="card shadow mb-4">

		<div class="card-body">

			<h6 class="m-0 font-weight-bold text-primary py-3">TG bot actions list:

				<?= $count ?>

			</h6>

			<div class="pager">

				<? for ($i = 0; $i < $cc; $i++) { ?>

					<div class="pgr_box" id="pager_<?= $i ?>" onclick="action_list(<?= $i ?>, '<?=$busc?>')">

						<?= $i + 1 ?>

					</div>

				<? } ?>

			</div>

			<div class="uploadload" id="uploadload"></div>

			<div class="table-responsive tg_bot_actions" id="action_list"></div>

			<div class="row">

				<div class="col-sm-12 col-md-5">

					<div class="dataTables_info" id="dataTable_info" role="status" aria-live="polite"></div>

				</div>

			</div>

		</div>

	</div>

<script>
function action_list(pg, busc) {
	document.getElementById('action_list').innerHTML = '<img class="loading" src="img/loading_modern.gif">';

	fetch('includes/tg_bot_actions_list_js.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify({ pg, busc })
	})
	.then(response => response.json()) 
	.then(data => {
		document.getElementById('debug').innerHTML = data.debug || '';
		document.getElementById('action_list').innerHTML = data.res;
		document.getElementById('dataTable_info').innerHTML = 'Showing: ' + data.cant + ' actions';

		for (let i = 0; i < <?= $cc ?>; i++) {
			let pageElem = document.getElementById('pager_' + i);
			if (pageElem) pageElem.style.backgroundColor = "#CCC";
		}

		let activePageElem = document.getElementById('pager_' + pg);
		if (activePageElem) activePageElem.style.backgroundColor = "#999";
	})
	.catch(error => {
		console.error('Failed to get actions list:', error);
		document.getElementById('action_list').innerHTML = '<p style="color:red;">Failed to get actions list</p>';
	});
}
function action_get_edit_form(id) {
    const editBox = document.getElementById('adm_list_edit_box' + id);
    const editIcon = document.getElementById('edit_icon_' + id);

    if (editBox.style.display === "grid") {
        editBox.style.display = "none";
        if (editIcon) editIcon.src = "img/edit.png";
        return;
    }

    fetch('includes/tg_bot_actions_edit_form_js.php', {
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
            console.error("Failed to get action edit form:", data.err);
            document.getElementById('debug').innerHTML = `<p style="color:red;">Error: ${data.err}</p>`;
        }
    })
    .catch(error => {
        console.error("Connection error:", error);
        document.getElementById('debug').innerHTML = `<p style="color:red;">Failed to get action edit form</p>`;
    });
}
function action_edit_save(id) {
    let data = {
        id:         id,
        userId:     document.getElementById('userId' + id)?.value       || '',
        username:   document.getElementById('username' + id)?.value     || '',
        command:    document.getElementById('command' + id)?.value      || '',
        parameter:  document.getElementById('parameter' + id)?.value    || '',
        status:     document.getElementById('status' + id)?.value       || '',
        failReason: document.getElementById('failReason' + id)?.value   || ''
    };

    fetch('includes/tg_bot_actions_edit_js.php', {
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

            document.getElementById('c0_' + id).innerHTML = responseData.action.id;
            document.getElementById('c1_' + id).innerHTML = responseData.action.userId;
            document.getElementById('c2_' + id).innerHTML = responseData.action.username;
            document.getElementById('c3_' + id).innerHTML = responseData.action.command;
            document.getElementById('c4_' + id).innerHTML = responseData.action.parameter;
            document.getElementById('c5_' + id).innerHTML = responseData.action.status;
            document.getElementById('c6_' + id).innerHTML = responseData.action.failReason;
            document.getElementById('c7_' + id).innerHTML = responseData.action.created_at;
            document.getElementById('c8_' + id).innerHTML = responseData.action.updated_at;
        } else {
            console.error("Failed to save:", responseData.err);
        }
    })
    .catch(error => {
        console.error("Connection error:", error);
    });
}
function action_del(id) {
	if (!confirm("Are you sure to delete this data?\nThere is no way to recover the deleted data")) {
        return;
    }

	fetch('includes/tg_bot_actions_del_js.php', {
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
                    countElem.innerHTML = 'Showing: ' + (currentCount - 1) + ' actions';
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
function action_list_by_filter() {
	let busc = document.getElementById('busc_texto').value;
	action_list(0,busc);
}

action_list(0,'');
</script>