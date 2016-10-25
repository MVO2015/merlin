function createDiv(appentTo, innerHTML) {
    div = document.createElement("div");
    div.innerHTML = innerHTML;
    document.getElementById(appentTo).appendChild(div);
}

function toggleImages(id) {
    var element = document.getElementById("testid" + id);
    if (element.childElementCount > 0) {
        element.removeChild(element.childNodes[0]);
    } else {
        loadImage(id);
    }
}

function loadImage(id)
{
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {
            var div = document.getElementById("testid" + id);
            var url = window.URL || window.webkitURL;
            div.innerHTML = this.response;
        }
    };
    xhr.open('GET', 'http://localhost:8080/merlin/app/getthumbnails.php?testid=' + id);
    xhr.responseType = 'text';
    xhr.send();
}

