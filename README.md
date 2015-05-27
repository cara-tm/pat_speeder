# pat-speeder
A Textpattern CMS plugin.

Did you ever seen the source code of the Google main html page? For speed reasons, Google serves its home page into one line of code. The benefit is a server bandwidth gain. So what don't we make the same for ours TXP websites?

Just activate this plugin and your page templates and database css files (not in this version) will be rendered into one line of code.

Warning: This plugin seems not to be compatible with some flash audio players (to be confirmed).
Test results

This plugin gives a benefit rendition between 5% (for precompressed pages) and 6% (normal pages) according to Ruud's observations.

Included automatic server side GZIP compression, if available, gives an average additional benefit of 75%.

This plugin seems to get better results than ask_header up to 0.7% (based on a vanilla default TXP installation).
