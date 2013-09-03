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
<div class="<?php print $classes ?>" id="<?php print $id ?>">
  <h2<?php if (!$title_visible): ?> class="element-invisible"<?php endif ?>><?php print $section_title ?></h2>
  <?php print render($title_suffix) ?>
  <div class="content" data-name="content" data-type="section" data-context="<?php print $context_id ?>">
    <?php print render($tiles) ?>
    <p class="top-link"><a href="#wrap">Back to top</a></p>
  </div>
</div>
