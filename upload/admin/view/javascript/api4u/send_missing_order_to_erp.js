window.onload = function () {
    popupElement = document.getElementById("myPopup");
}

var popupElement = null;
var popupTimeout = null;

function sendToERP() {
    clearTimeout(popupTimeout);
    popupElement.classList.remove("show");
    var queryString = window.location.search;
    var urlParams = new URLSearchParams(queryString);
    var orderId = urlParams.get('order_id');
    var token = urlParams.get('user_token');

    if (orderId == null || token == null) {
        return;
    }
    $.confirm({
        title: 'Send order to ERP',
        content: 'Are you sure?',
        buttons: {
            yes: function () {
                let progressBar = document.createElement('div');
                progressBar.className = 'progress progress-sync-bar';
                progressBar.setAttribute('style', 'width:100%; margin: auto;');
                let AnimationBar = document.createElement('div');
                AnimationBar.className = 'progress-bar progress-bar-striped progress-bar-animated';
                progressBar.appendChild(AnimationBar);
                let syncBar = document.getElementById('sync-bar');
                syncBar.appendChild(progressBar);

                document.querySelector('.btn-send-missed-order').disabled = true;

                if (token != null) {
                    $.ajax({
                        url: 'index.php?route=extension/module/api4u_post_missed_order&user_token=' + token,
                        type: 'post',
                        data: {
                            order_id: orderId
                        },
                        error: function () {
                            document.querySelector('.progress-sync-bar').remove();
                            document.querySelector('.btn-sync').disabled = false;
                            popup('Unexpected error.');
                        },
                        success: function (response) {
                            response = JSON.parse(response);
                            popup(response.message);
                            document.querySelector('.progress-sync-bar').remove();
                            document.querySelector('.btn-send-missed-order').disabled = false;
                        }
                    });
                }
            },
            no: function () {
            }
        }
    });
}

function popup(message) {
    popupElement.innerText = message;
    popupElement.classList.add("show");
    popupTimeout = setTimeout(() => popupElement.classList.remove('show'), 3000);
}