<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.9.1/font/bootstrap-icons.min.css">
    <script src="js.js"></script>
    <link rel="icon" href="https://soboliucho.cz/img/icon.ico">
    <title>Ukol</title>
</head>

<body>
    <h1>Vyhledávání</h1>
    <div>
        <form action="" id="search">
            <div>
                <input type="text" placeholder="Hledání" id="input">
            </div>
            <div>
                <button type="submit"><i class="bi bi-search"></i>Hledat</button>
            </div>
        </form>
    </div>
    <div id="result">
        <!-- <div>
            <select name="" id="downald">
                <option value="0">JSON</option>
                <option value="1">XML</option>
            </select>
        </div>
        <div>
            <button id="download">Stáhnout</button>
        </div> -->
    </div>

    <script>
        let form = document.getElementById("search");
        //listener for form submit to prevent default action
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            let search = document.getElementById("input").value;
            let result = document.getElementById("result");
            //check if search value is empty
            if (search === "") {
                result.innerHTML = "Není vyplněno pole hledání";
                return;
            }

            //send request to server with search value
            let xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    let response = JSON.parse(xhr.responseText);
                    console.log(response["status"]);
                    //work with response data
                    make_result(response, result);
                }
            };
            //send data to server
            var data = new FormData();
            data.append('search', search);
            xhr.open("POST", "search.php", true);
            xhr.send(data);
        });
    </script>

</body>

</html>