/**
 * Load it with:
 * s=document.createElement('script');s.src='http://stapibas.bogo/js/show-links.js';document.body.appendChild(s);
 */
var scripts = document.getElementsByTagName("script");
var thisScript = scripts[scripts.length-1];
var thisScriptsSrc = thisScript.src;
var stapibasUrl = thisScriptsSrc.replace('js/show-links.js', '');
//var stapibasUrl = 'http://stapibas.bogo/';

var pageUrl = window.location.href;

function loadScript(url, callback)
{
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = url;
    script.onreadystatechange = callback;
    script.onload = callback;
    document.getElementsByTagName('head')[0].appendChild(script);
}

function loadData()
{
    jQuery('head').append(
        '<link rel="stylesheet" type="text/css" href="'
        + stapibasUrl + 'css/show-links.css" />'
        + '<link rel="stylesheet" type="text/css" href="'
        + stapibasUrl + 'css/jquery-0.6.1.smallipop.min.css" />'
    );
    jQuery.ajax(
        stapibasUrl + 'api/links.php?url='
        + encodeURIComponent(fixUrl(pageUrl))
    ).done(function(data) {showData(data);})
        .fail(function(data) {showError(data);});
}

function showData(data)
{
    var items = jQuery('.e-content a');
    if (items.length == 0) {
        items = jQuery('a');
    }

    //collect stats
    var stats = {
        links: 0
    };
    $.each(data.links, function(key, link) {
        stats.links++;
        if (!stats[link.status]) {
            stats[link.status] = 1;
        } else {
            stats[link.status]++;
        }
    });
    var statlist = '';
    $.each(stats, function(status, count) {
        statlist = statlist + '<li>' + count + ' ' + status + '</li>';
    });
    $('body').prepend(
        '<div class="stapibas-stats">'
            + '<b>Linkback stats</b>: '
            + '<ul>' + statlist + '</ul>'
            + '</div>'
    );

    //add link info
    items.each(function(key, elem) {
        if (!data.links[fixUrl(elem.href)]) {
            return;
        }
        var link = data.links[fixUrl(elem.href)];
        $(elem).addClass('stapibas-link')
            .addClass('stapibas-status-' + link.status);
        $(elem).smallipop(
            {
                theme: 'white'
            },
            '<h2>Linkback information</h2>'
                + '<dl>'
                + '<dt>Status</dt><dd>' + link.status + '</dd>'
                + '<dt>Pinged</dt><dd>' + link.pinged + '</dd>'
                + '<dt>Updated</dt><dd>' + link.updated + '</dd>'
                + '<dt>Tries</dt><dd>' + link.tries + '</dd>'
                + '<dt>Error code</dt><dd>' + link.error.code + '</dd>'
                + '<dt>Error message</dt><dd>' + link.error.message + '</dd>'
                + '</dl>'
        );
    });
}

function fixUrl(url)
{
    return url.replace(/www.bogo/, 'cweiske.de');
}

function showError(data)
{
    $('body').prepend(
        '<div class="stapibas-stats stapibas-stats-error">'
            + 'Error loading linkback data: '
            + data.status + ' <b>' + data.statusText + '</b>'
            + '</div>'
    );
}

loadScript(
    stapibasUrl + 'js/jquery-2.1.0.js',
    function() {
        loadScript(stapibasUrl + 'js/jquery-0.6.1.smallipop.js', loadData);
    }
);
