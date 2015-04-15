<?php

/**
 *
 * @file
 * Default theme implementation to display a section within a sectioned page.
 *
 * Available variables:
 * - $section_title: Title of section
 * - $tiles: render array of available tiles.
 *
 */
?>
<div data-type="region" data-name="section" class="<?php print $classes ?>" id="<?php print $id ?>"<?php print $attributes ?>>
  <div class="container header">
    <div class="row">
      <div class="header block col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <h2<?php if (!$title_visible): ?> class="element-invisible"<?php endif ?>><?php print $section_title ?></h2>
        <?php print render($title_suffix) ?>
      </div>
    </div>
  </div>
  <div class="container content" data-tiles-selector="<?php print $selector ?>">
    <?php print render($tiles) ?>
  </div>
  <div class="container footer">
    <div class="row">
      <div class="block col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <p class="top-link"><a href="#wrap">Back to top</a></p>
      </div>
    </div>
  </div>
</div>
