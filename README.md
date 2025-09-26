# Markdown PHP Editor

It’s a simple Markdown editor (HTML/JS/CSS) packaged for easy embedding in PHP apps via Composer.

![editor](https://dev.heinisch-design.de/demo/shared/md-editor/markdown-edit.png)

Editor with active preview on the right side

![With active preview](https://dev.heinisch-design.de/demo/shared/md-editor/markdown-preview.png)

## Requirements

* PHP >= 8.1
* Composer

## Installation

`composer require cheinisch/markdown-editor`

## Usage

### Required functions

Import the class
```
use cheinisch\MarkdownEditor\MarkdownEditor;
```

Loading CSS in the head
```
<?= MarkdownEditor::renderHeadAssets(); ?>
```
Loading JS in the footer area
```
<?= MarkdownEditor::renderFootAssets(); ?>
```
Loading the editor in the main area
```
<?= MarkdownEditor::render(); ?>
```

### Demo Page

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
