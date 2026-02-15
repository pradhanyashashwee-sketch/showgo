// popup.js

document.addEventListener('DOMContentLoaded', function() {
    // Get the modal element
    var modal = document.getElementById("offerModal");

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close-btn")[0];

    // Function to show the modal
    function showModal() {
        modal.style.display = "block";
    }

    // Show the modal after a short delay (e.g., 500 milliseconds)
    // This ensures the page is fully loaded before the pop-up appears.
    setTimeout(showModal, 500);

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
});