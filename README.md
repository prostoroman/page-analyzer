# Page analyzer
Calculate frequency of words for html content

```php
$analyzer = new PageAnalyzer\Analyzer();
$stats = $analyzer->analyse('http://www.lipsum.com/');
var_dump($stats);
```

## Stop Words

```php
$stopWords = ['в', 'и', 'от', 'для'];
$analyzer = new PageAnalyzer\Analyzer(['stopWords' => $stopWords]);
```
