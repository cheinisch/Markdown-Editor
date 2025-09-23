# Markdown PHP Editor

It’s a simple Markdown editor (HTML/JS/CSS) packaged for easy embedding in PHP apps via Composer.

## Requirements

* PHP >= 8.1
* Composer

## Installation

`composer require cheinisch/markdown-editor`

## Usage

Simple PHP HTML Layout with required functions
```
<?php
    require __DIR__ . '/vendor/autoload.php';
    use cheinisch\MarkdownEditor\MarkdownEditor;
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Editor</title>
  <?= MarkdownEditor::renderHeadAssets(); ?>   <!-- CSS -->
</head>
<body>
    <?= MarkdownEditor::render(); ?>   
    This is a dummy text
    <?= MarkdownEditor::renderFootAssets(); ?>  <!-- JS at the end -->
</body>
</html>
```
