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
<div class="<?php print $classes ?>" id="<?php print $id ?>" data-context="<?php print $context_id ?>">
  <h2><?php print $section_title ?></h2>
  <?php print render($title_suffix) ?>
  <div class="content" data-name="content" data-type="region">
    <?php print render($tiles) ?>
  </div>
</div>
