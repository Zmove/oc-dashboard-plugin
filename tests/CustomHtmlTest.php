<?php namespace Zmove\Dashboard\Tests;

use PluginTestCase;
use Zmove\Dashboard\ReportWidgets\CustomHtml;

/**
 * CustomHtmlTest covers how the custom HTML block leaves its scripts inert for
 * custom-html.js to run, while keeping the rest of the HTML untouched.
 */
class CustomHtmlTest extends PluginTestCase
{
    /**
     * testInlineScriptIsDeferred
     */
    public function testInlineScriptIsDeferred()
    {
        $this->assertSame(
            '<p>Bonjour</p><script type="text/x-zmove-deferred">console.log(1)</script>',
            CustomHtml::deferScripts('<p>Bonjour</p><script>console.log(1)</script>')
        );
    }

    /**
     * testOwnScriptTypeIsKeptForTheRunner, whatever its quoting.
     */
    public function testOwnScriptTypeIsKeptForTheRunner()
    {
        $this->assertSame(
            '<script type="text/x-zmove-deferred" data-zmove-type="module">import x from "/x.js"</script>',
            CustomHtml::deferScripts('<script type="module">import x from "/x.js"</script>')
        );
        $this->assertSame(
            '<script type="text/x-zmove-deferred" data-zmove-type="text/javascript" src="/a.js"></script>',
            CustomHtml::deferScripts("<SCRIPT type='text/javascript' src=\"/a.js\"></script>")
        );
    }

    /**
     * testExternalScriptAttributesAreKept
     */
    public function testExternalScriptAttributesAreKept()
    {
        $this->assertSame(
            '<script type="text/x-zmove-deferred" src="https://example.com/widget.js" data-id="42" defer></script>',
            CustomHtml::deferScripts('<script src="https://example.com/widget.js" data-id="42" defer></script>')
        );
    }

    /**
     * testHtmlWithoutScriptIsUntouched, including attributes merely containing "type".
     */
    public function testHtmlWithoutScriptIsUntouched()
    {
        $html = '<div class="box" style="color:red"><input type="text"><noscript>x</noscript><a href="#">lien</a></div>';

        $this->assertSame($html, CustomHtml::deferScripts($html));
    }
}
