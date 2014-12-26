var autoPublish = (window.location.hostname == 'rchetype.co');
if(typeof $ === 'undefined') {
    alert("@rchetype not found.  Running @ssemble...");
    window.location = 'assemble.php';
}
else if(autoPublish) {
    $.ajax('publish.php?publish=dev');
}