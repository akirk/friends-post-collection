# Readability.php

[![Latest Stable Version](https://poser.pugx.org/fivefilters/readability.php/v/stable)](https://packagist.org/packages/fivefilters/readability.php) [![Tests](https://github.com/fivefilters/readability.php/actions/workflows/main.yml/badge.svg?branch=master)](https://github.com/fivefilters/readability.php/actions/workflows/main.yml)

PHP port of *Mozilla's* **[Readability.js](https://github.com/mozilla/readability)**. Parses html text (usually news and other articles) and returns **title**, **author**, **main image** and **text content** without nav bars, ads, footers, or anything that isn't the main body of the text. Analyzes each node, gives them a score, and determines what's relevant and what can be discarded.

![Screenshot](https://raw.githubusercontent.com/fivefilters/readability.php/assets/screenshot.png)

The project aim is to be a 1 to 1 port of Mozilla's version and to follow closely all changes introduced there, but there are some major differences on the structure. Most of the code is a 1:1 copy –even the comments were imported– but some functions and structures were adapted to suit better the PHP language.

**Original Developer**: Andres Rey

**Developer/Maintainer**: FiveFilters.org

## Code porting

Master branch - Up to date on 26 August 2021, with the exception of a [piece of code](https://github.com/fivefilters/readability.php/commit/1c662465bded2ab3acf3b975a1315c8c45f0bf73#diff-b9b31807b1a39caec18ddc293e9c52931ba8b55191c61e6b77a623d699a599ffR1899) which doesn't produce the same results in PHP for us compard to the JS version. Perhaps there's an error, or some difference in the underlying code that affects this. If you know what's wrong, please feel free to drop us a note or submit a pull request. :)

Version 2.1.0 - Up to date with Readability.js up to [19 Nov 2018](https://github.com/mozilla/readability/commit/876c81f710711ba2afb36dd83889d4c5b4fc2743).

## Requirements

PHP 7.4+, ext-dom, ext-xml, and ext-mbstring. To install these dependencies (in the rare case your system does not have them already), you could try something like this in *nix like environments:

`$ sudo apt-get install php7.4-xml php7.4-mbstring`

## How to use it

First you have to require the library using composer:

`composer require fivefilters/readability.php`

Then, create a Readability class and pass a Configuration class, feed the `parse()` function with your HTML and echo the variable:

```php
<?php
require __DIR__ . '/vendor/autoload.php';
use fivefilters\Readability\Readability;
use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;

$readability = new Readability(new Configuration());

$html = file_get_contents('http://your.favorite.newspaper/article.html');

try {
    $readability->parse($html);
    echo $readability;
} catch (ParseException $e) {
    echo sprintf('Error processing text: %s', $e->getMessage());
}
```

Your script will output the parsed text or inform about any errors. You should always wrap the `->parse` call in a try/catch block because if the HTML cannot be parsed correctly, a `ParseException` will be thrown.

If you want to have a finer control on the output, just call the properties one by one, wrapping it with your own HTML.

```php
<h1><?= $readability->getTitle(); ?></h1>
<h2>By <?= $readability->getAuthor(); ?></h2>
<div class="content"><?= $readability->getContent(); ?></div>

```

Here's a list of the available properties:

- Article title: `->getTitle();`
- Article content: `->getContent();`
- Excerpt: `->getExcerpt();`
- Main image: `->getImage();`
- All images: `->getImages();`
- Author: `->getAuthor();`
- Text direction (ltr or rtl): `->getDirection();`

If you need to tweak the final HTML you can get the DOMDocument of the result by calling `->getDOMDocument()`.

## Options

You can change the behaviour of Readability via the Configuration object. For example, if you want to fix relative URLs and declare the original URL, you could set up the configuration like this:

```php
$configuration = new Configuration();
$configuration
    ->setFixRelativeURLs(true)
    ->setOriginalURL('http://my.newspaper.url/article/something-interesting-to-read.html');
```
Also you can pass an array of configuration parameters to the constructor:
```php
$configuration = new Configuration([
    'fixRelativeURLs' => true,
    'originalURL'     => 'http://my.newspaper.url/article/something-interesting-to-read.html',
    // other parameters ... listing below
]);
```


Then you pass this Configuration object to Readability. The following options are available. Remember to prepend `set` when calling them using native setters.

- **MaxTopCandidates**: default value `5`, max amount of top level candidates.
- **CharThreshold**: default value `500`, minimum amount of characters to consider that the article was parsed successful.
- **ArticleByLine**: default value `false`, search for the article byline and remove it from the text. It will be moved to the article metadata. 
- **StripUnlikelyCandidates**: default value `true`, remove nodes that are unlikely to have relevant information. Useful for debugging or parsing complex or non-standard articles. 
- **CleanConditionally**: default value `true`, remove certain nodes after parsing to return a cleaner result. 
- **WeightClasses**: default value `true`, weight classes during the rating phase. 
- **FixRelativeURLs**: default value `false`, convert relative URLs to absolute. Like `/test` to `http://host/test`. 
- **SubstituteEntities**: default value `false`, disables the `substituteEntities` flag of libxml. Will avoid substituting HTML entities. Like `&aacute;` to á.
- **NormalizeEntities**: default value `false`, converts UTF-8 characters to its HTML Entity equivalent. Useful to parse HTML with mixed encoding.
- **OriginalURL**: default value `http://fakehost`, original URL from the article used to fix relative URLs.
- **KeepClasses**: default value `false`, which removes all `class="..."` attribute values from HTML elements.
- **Parser**: default value `html5`, which uses HTML5-PHP for parsing. Set to `libxml` to use that instead (not recommended for modern HTML documents).
- **SummonCthulhu**: default value `false`, remove all `<script>` nodes via regex. This is not ideal as it might break things, but if you've set the parser to libxml (see above), it might be the only solution to [libxml problems with unescaped javascript](https://github.com/fivefilters/readability.php#known-libxml-parsing-issues).

### Debug log

Logging is optional and you will have to inject your own logger to save all the debugging messages. To do so, use a logger that implements the [PSR-3 logging interface](https://github.com/php-fig/log) and pass it to the configuration object. For example:

```php
// Using monolog

$log = new Logger('Readability');
$log->pushHandler(new StreamHandler('path/to/my/log.txt'));

$configuration->setLogger($log);
```

In the log you will find information about the parsed nodes, why they were removed, and why they were considered relevant to the final article.

## Limitations

Of course the main limitation is PHP. Websites that load the content through lazy loading, AJAX, or any type of javascript fueled call will be ignored (actually, *not ran*) and the resulting text will be incorrect, compared to the readability.js results. All the articles you want to parse with readability.php need to be complete and all the content should be in the HTML already.  

## Known libxml parsing issues

Readability.php as of version 3.0.0 uses a HTML5 parser. Earlier versions used libxml. The issues below apply to libxml parsing, so if you're using an earlier version of Readability.php (pre 3.0.0), or if you've set the parser to libxml in the configuration, read on...

### Javascript spilling into the text body

DOMDocument has some issues while parsing javascript with unescaped HTML on strings. Consider the following code:

```html
<div> <!-- Offending div without closing tag -->
<script type="text/javascript">
       var test = '</div>';
       // I should not appear on the result
</script>
```

If you would like to remove the scripts of the HTML (like readability does), you would expect ending up with just one div and one comment on the final HTML. The problem is that libxml takes that closing div tag inside the javascript string as a HTML tag, effectively closing the unclosed tag and leaving the rest of the javascript as a string within a P tag. If you save that node, the final HTML will end up like this:

```html
<div> <!-- Offending div without closing tag -->
<p>';
       // I should not appear on the result
</p></div>
```

This is a libxml issue and not a Readability.php bug.

There's a workaround for this: using the `summonCthulhu` option. This will remove all script tags **via regex**, which is not ideal because you may end up summoning [the lord of darkness](https://stackoverflow.com/a/1732454).

### &nbsp entities disappearing

`&nbsp` entities are converted to spaces automatically by libxml and there's no way to disable it.

### Self closing tags rendering as fully expanded tags

Self closing tags like `<br />` get automatically expanded to `<br></br`. No way to disable it in libxml.

## Dependencies

Readability.php uses

 * [HTML5-PHP](https://github.com/Masterminds/html5-php) to parse and serialise HTML.
 * [PSR Log](https://github.com/php-fig/log) interface to define the allowed type of loggers. 
 * [Monolog](https://github.com/Seldaek/monolog) is only required on development installations. (`--dev` option during `composer install`).

## To-do

- Keep up with Readability.js changes
- Add a small template engine for the __toString() method, instead of using a hardcoded one.
- Replace all the `iterator_to_array` calls with a custom PHP generator that keeps track of the removed or altered nodes.

## How it works

Readability parses all the text with DOMDocument, scans the text nodes and gives the a score, based on the amount of words, links and type of element. Then it selects the highest scoring element and creates a new DOMDocument with all its siblings. Each sibling is scored to discard useless elements, like nav bars, empty nodes, etc.

## Security

If you're going to use Readability with untrusted input (whether in HTML or DOM form), we **strongly** recommend you use a sanitizer library like [HTML Purifier](https://github.com/ezyang/htmlpurifier) to avoid script injection when you use
the output of Readability. We would also recommend using [CSP](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP) to add further defense-in-depth
restrictions to what you allow the resulting content to do. The Firefox integration of
reader mode uses both of these techniques itself. Sanitizing unsafe content out of the input is explicitly not something we aim to do as part of Readability itself - there are other good sanitizer libraries out there, use them!

## Testing

Any version of PHP from 7.4 and above installed locally should be enough to develop new features and add new test cases. If you want to be 100% sure that your change doesn't create any issues with other versions of PHP, you can use the provided Docker containers to test currently in 7.4, 8.0, 8.1.

If you use composer to download this this package, make sure you pass the `--prefer-source` flag, otherwise the `test/` folder won't be downloaded.

You'll need Docker and Docker Compose for this. To run all the tests in the three PHP versions above, just type the following command:

```bash
make test-all
```

This will start all the containers and run all the tests on every supported version of PHP. If you want to test against a specific version, you can use `make test-7.4`, `make test-8.0`, or `make test-8.1`.

### Different versions of libxml

If you want to test against supported versions of PHP *AND* multiple versions of libxml, run `test-all-versions`. This will test against PHP versions 7.4 to 8.1 and libxml versions 2.9.10, 2.9.13 and 2.9.14. Normally you won't need to do this unless you think you've found a bug on an specific version of libxml.

### Updating the expected tests

If you've made an improvement to the code, you'll probably want to examine the Readability.php output for the test cases here. To do that, run the following command first from the root of the project folder:

    docker-compose up -d php-7.4-libxml-2.9.10

You should now have a docker image running with the project root folder mapped to /app/ on your Docker instance (see `docker-compose.yml`). Any changes to these files will be accessible from the Docker instance from now on.

Next, create a folder in tests/ called /changed, then run the following command to run the test suite:

    docker-compose exec -e output-changes=1 -e output-diff=1 php-7.4-libxml-2.9.10 php /app/vendor/phpunit/phpunit/phpunit --configuration /app/phpunit.xml

The two environment variables (`output-changes=1` and `output-diff=1`) will result in new output for any failing test (along with a diff of changes) being written to the changed/ folder.

If you're happy the changes are okay, set `output-diff=0` and the diff files will no longer be written, making it easier to copy the new expected output files over to their corresponding locations in test-pages\.

## License

Based on Arc90's readability.js (1.7.1) script available at: http://code.google.com/p/arc90labs-readability

    Copyright (c) 2010 Arc90 Inc

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
