$(document).ready(function() {
    const API_URL = 'http://localhost:8000/api.php';


    // Function to fetch gallery data by path
    function fetch(path) {
        var apiUrl;
        if(path == undefined) {
            apiUrl = API_URL;
        } else {
            apiUrl = API_URL + '?path=' + path;
        }

        $.ajax({
            url: apiUrl,
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                // Handle the data returned by the API
                console.log("Done Fetching..., Pouplating Thumbnails");

                populateThumbnails(data);
            },
            error: function(error) {
                console.error('API request failed:', error);
                hideLoader();
            }
        });
    }

    // Function to populate thumbnails
    function populateThumbnails(data) {
        // Clear existing thumbnails
        $(".thumbnails").empty();

        console.log("Clearing Content Done, Looping and adding DOM elements");

        // Loop through the data and create thumbnails
        data.forEach(function(item) {
            console.log("Adding items " + item.type);

            var $thumbnail = $("<li class='thumbnail'></li>");
            $thumbnail.data("path", item.path);
            $thumbnail.data("type", item.type);
            $thumbnail.on("click", function(e) {
                onThumbnailClick($(e.currentTarget));
            });

            if (item.type == 'image') {
                $thumbnail.append($("<img src='" + item.square_thumbnail + "'>"));
            }

            $(".thumbnails").append($thumbnail);
        });

        hideLoader();
    }

    function onThumbnailClick($el) {
        if ($el.data("type") == 'directory') {
            showLoader();
            fetch($el.data("path"));
        }
    }

    function showLoader() {
        $('.loader-container').css('display', 'flex');
    }

    function hideLoader() {
        $('.loader-container').css('display', 'none');
    }

    // // Handle thumbnail click event to display the popup
    // $('.thumbnails').on("click", function(a, b, c) {
    //     var $thumbnail = $(this);
    //
    //     console.log(a, b, c);
    //
    //     // var url = $thumbnail.data("path");
    //     // var type = $thumbnail.data("type");
    //     //
    //     // if (type == 'directory') {
    //     //     fetch(url);
    //     // }
    //
    //     //const imageSrc = $(this).data("image-src");
    //     //$("#popupImage").attr("src", imageSrc);
    //     //$(".popup").show();
    // });

    // Close the popup
    $("#closePopup").on("click", function() {
        $(".popup").hide();
    });

    // Handle view switch
    $("input[type='radio']").on("change", function() {
        const view = $(this).attr("id");
        // Update the gallery view based on the selected view type (square/proportional)
        if (view === "squareView") {
            // Implement square view
            $(".thumbnails").addClass("square-view").removeClass("proportional-view");
        } else {
            // Implement proportional view
            $(".thumbnails").addClass("proportional-view").removeClass("square-view");
        }
    });

    fetch();
});
