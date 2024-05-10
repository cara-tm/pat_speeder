# pat-speeder ![license](https://img.shields.io/github/license/cara-tm/pat_speeder.svg?maxAge=3600) ![version](https://img.shields.io/github/tag/cara-tm/pat_speeder.svg) ![Textpattern 4.8+](https://img.shields.io/badge/Textpattern-4.8%2B-brightgreen.svg?maxAge=3600)

A Textpattern CMS plugin. ![Last Commit](https://img.shields.io/github/last-commit/cara-tm/pat_speeder.svg)

![Screenshot of the plugin.](preview.jpg)

Have you seen the source code of the Google homepage? For speed reasons, Google serves its homepage as a single line of code. The benefit is a reduction in file size and bandwidth usage. Now we can do the same for our TXP websites.

Just activate this plugin and your page templates will be rendered as a single line of code.

    <txp:pat_speeder enable="1" gzip="1" /><!DOCTYPE ...

See the plugin help for details of the attributes available.

**Warning**: This plugin may not be compatible with some flash audio players (to be confirmed).

According to [Ruud van Melick](https://vanmelick.com/)'s observations, this plugin results in a reduction of between 5% (for precompressed pages) and 6% (normal pages).

Included automatic server side GZIP compression, if available, gives an average additional benefit of 75%.

This plugin (v 0.7) seems to get **better results** than aks_header (v 0.3.6) **up to 0.7%** (based on a vanilla default TXP installation).

## Preferences settings

After installation, go to your website preferences:

**Enable pat_speeder?** Enable or disable the plugin rendering on all your pages where it is used (only if the "enable" attibute is set on "1");

**Enable GZIP compression with pat_speeder?** Choose to activate the internal GZIP compression if needed (you may set your .htaccess file instead) only if the gzip attribute isn't set in this plugin tag;

**List of tags to protect from pat_speeder:** a comma separated list of tags that the compression should skip. Default: `script, svg, pre, code`. Note: `textarea` is included in the plugin;

**Enable extreme compression?** This is a “compact mode” that removes all unnecessary spaces (to be precise: *2 or more*) between all tags to return a smaller document in size. Bear in mind that your page and form markup must be cleanly written without additional spaces around tags. This can otherwise result in unexpected results such as text content directly adjacent to one another (e.g. a link running straight into text). As such, this settings is recommended only for advanced users.




