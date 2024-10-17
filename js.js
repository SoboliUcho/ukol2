// paste downald function to the page with result
function make_result(response, result) {
    let json_url = response["json"];
    let xml_url = response["xml"];
    result.innerHTML = `<div>
            <select name="" id="downald">
                <option value="0">JSON</option>
                <option value="1">XML</option>
            </select>
        </div>
        <div>
            <button id="download"><i class="bi bi-download"></i>Stáhnout</button>
        </div>`;
        
    let download = document.getElementById("download");
    // add event listener to download button to download selected file
    download.addEventListener("click", function () {
        let selectedOption = document.getElementById("downald").value;
        if (selectedOption == 0) {
            downald("search.json", json_url);
        } else if (selectedOption == 1) {
            downald("search.xml", xml_url);
        }
    });
}

// open url as download file with set name
function downald(type, url) {
    let a = document.createElement("a");
    a.href = url;
    a.download = type;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}