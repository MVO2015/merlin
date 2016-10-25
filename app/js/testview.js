function toggleList($id) {
    var listItem = document.getElementById($id);

    if (listItem.className.indexOf("collapsed") >=0) {
        listItem.className = listItem.className.replace("collapsed", "expanded");
    } else {
        listItem.className = listItem.className.replace("expanded", "collapsed");
    }
}
