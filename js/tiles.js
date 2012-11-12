(function ($) {
  Drupal.behaviors.tiles = {
    attach: function(context, settings) {
      $(Tiles.prototype.selector.moveLink, context).click(function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(e.target).blur();
        if ($(e.target).closest(Tiles.prototype.selector.tile).hasClass('dragging')) {
          return;
        }
        block = new Tiles(e.target);
        block.setDraggable();
      });

      $(Tiles.prototype.selector.resizeLink, context).once('block-width', function() {
        $(this).click(function(e) {
          e.preventDefault();
          e.stopPropagation();
          $(e.target).blur();
          if ($(e.target).closest(Tiles.prototype.selector.tile).hasClass('dragging')) {
            return;
          }
          block = new Tiles(e.target);
          block.setResizable();
        });
      });
    }
  };

  /**
   * @class Tiles
   *   Encapsulate js functionality for a tile.
   */

  Tiles = function(domNode) {
    $d = $(domNode);
    this.domNode = $d.attr('data-type') === 'block' ? $d : $d.closest(this.selector.tile);
    // Close the contextual links.
    this.domNode.closest('.contextual-links-region').mouseleave();
    this.region = this.domNode.closest(this.selector.region);
    this.module = this.domNode.attr('data-module');
    this.delta = this.domNode.attr('data-delta');
    this.width = parseInt(this.domNode.attr('data-width'), 10);;
  };

  Tiles.prototype.selector = {
    tile: '.tile',
    region: '[data-type="region"]',
    row: '.row-fluid',
    moveLink: '.contextual-links .block-arrange a',
    resizeLink: '.contextual-links .block-set-width a'
  };

  Tiles.prototype.setDraggable = function() {
    this.domNode.addClass('dragging');
    $('body').addClass('dragging');
    this.addMoveOverlay();
    return this;
  };

  Tiles.prototype.unsetDraggable = function() {
    this.domNode.removeClass('dragging');
    $('body').removeClass('dragging');
    this.removeMoveOverlay();
    return this;
  };

  /**
   * TODO Use jQuery template
   */
  Tiles.prototype.addMoveOverlay = function() {
    // Prevent irresponsible js plugins (twitter I'm looking at you) from using
    // document.write after a block is moved. Using document.write after a page
    // load overwrites the whole dom.
    document.write = function() {};

    var overlayContent = '<button class="move-left">Left</button>';
    overlayContent += '<button class="move-right">Right</button>';
    overlayContent += '<button class="save">Save</button>';
    overlayContent += '<span class="cancel">Cancel</span>';
    this.domNode.prepend('<div class="tile-overlay"><div class="inner"><div class="control-wrapper">' + overlayContent + '</div></div></div>');
    $('.move-left', this.domNode).click($.proxy(this,'moveLeft'));
    $('.move-right', this.domNode).click($.proxy(this,'moveRight'));
    $('.cancel', this.domNode).click($.proxy(this, 'moveCancel'));
    $('.save', this.domNode).click($.proxy(this, 'saveManifest'));
    return this;
  };

  Tiles.prototype.removeMoveOverlay = function() {
    $('.tile-overlay', this.domNode).remove();
    return this;
  };

  Tiles.prototype.moveLeft = function(e) {
    var manifest = this.regionManifest();
    if (manifest.blocks[0].module === this.module &&
        manifest.blocks[0].delta === this.delta) {
      alert('This is already the first block in this region.');
      return false;
    }
    var tile_index = manifest.blockIndex[this.module + '-' + this.delta];
    var prev_tile_index = tile_index - 1;
    var tile_weight = manifest.blocks[tile_index].weight;
    manifest.blocks[tile_index].weight = manifest.blocks[prev_tile_index].weight;
    manifest.blocks[prev_tile_index].weight = tile_weight;
    this.requestRegion(manifest, $.proxy(function() {
      $("[data-module='" + this.module + "'][data-delta='" + this.delta + "'] " + this.selector.moveLink).click();
    }, this));
    return false;
  };

  Tiles.prototype.moveRight = function(e) {
    var manifest = this.regionManifest();
    if (manifest.blocks[manifest.blocks.length-1].module === this.module &&
        manifest.blocks[manifest.blocks.length-1].delta === this.delta) {
      alert('This is already the last block in this region.');
      return false;
    }
    var tile_index = manifest.blockIndex[this.module + '-' + this.delta];
    var next_tile_index = tile_index + 1;
    var tile_weight = manifest.blocks[tile_index].weight;
    manifest.blocks[tile_index].weight = manifest.blocks[next_tile_index].weight;
    manifest.blocks[next_tile_index].weight = tile_weight;
    this.requestRegion(manifest, $.proxy(function() {
      $("[data-module='" + this.module + "'][data-delta='" + this.delta + "'] " + this.selector.moveLink).click();
    }, this));
    return false;
  };

  Tiles.prototype.moveCancel = function(e) {
    window.location.reload();
  };

  Tiles.prototype.regionManifest = function() {
    var region = this.region.attr('data-name');
    var manifest = {
      region: region,
      activeContext: Drupal.settings.tiles.active_context,
      blockIndex: {},
      blocks: []
    };
    $(this.selector.tile, this.region).each(function(i) {
      var $t = $(this);
      var module = $t.attr('data-module');
      var delta = $t.attr('data-delta');
      manifest.blockIndex[module + '-' + delta] = i;
      manifest.blocks.push({
        module: module,
        delta: delta,
        region: region,
        width: parseInt($t.attr('data-width'), 10),
        weight: i
      });
    });
    return manifest;
  };

  Tiles.prototype.requestRegion = function(manifest, callback) {
    $.ajax({
      type: 'POST',
      url: window.location.toString(),
      headers: {'X-TILES': 1},
      data: JSON.stringify(manifest),
      dataType: 'html',
      success: [
        $.proxy(this.handleRequestRegionSuccess, this),
        callback
      ],
      error: $.proxy(this, 'handleRequestRegionError')
    });
  };

  Tiles.prototype.handleRequestRegionSuccess = function(data, textStatus, jqXHR) {
    this.region.html(data);
    Drupal.attachBehaviors(this.region, Drupal.settings);
  };

  Tiles.prototype.handleRequestRegionError = function(jqXHR, textStatus, errorThrown) {};

  Tiles.prototype.saveManifest = function() {
    var manifest = this.regionManifest();
    $.ajax({
      type: 'POST',
      url: '/admin/tiles-save-tiles',
      data: JSON.stringify(manifest),
      dataType: 'json',
      success: $.proxy(this.saveHandleSuccess, this),
      error: $.proxy(this.saveHandleError, this)
    });
    return false;
  };

  Tiles.prototype.saveHandleSuccess = function() {
    this.unsetDraggable();
    this.unsetResizable();
  };

  Tiles.prototype.saveHandleError = function() {
    alert('Sorry, there was a problem saving the updated layout. Please try again after the page reloads.');
    window.location.reload();
  };

  Tiles.prototype.setResizable = function() {
    this.domNode.addClass('resizing');
    $('body').addClass('resizing');
    this.addResizeOverlay();
    return this;
  };

  Tiles.prototype.unsetResizable = function() {
    this.domNode.removeClass('resizing');
    $('body').removeClass('resizing');
    this.removeResizeOverlay();
    return this;
  };

  /**
   * TODO Use jQuery template
   */
  Tiles.prototype.addResizeOverlay = function() {
    // Prevent irresponsible js plugins (twitter I'm looking at you) from using
    // document.write after a block is moved. Using document.write after a page
    // load overwrites the whole dom.
    document.write = function() {};

    var overlayContent = '<span class="width-current">' + Drupal.settings.tiles.steps[this.width] + '</span>';
    overlayContent += '<button class="width-minus">-</button>';
    overlayContent += '<button class="width-plus">+</button>';
    overlayContent += '<button class="save">Save</button>';
    overlayContent += '<span class="cancel">Cancel</span>';
    this.domNode.prepend('<div class="tile-overlay"><div class="inner"><div class="control-wrapper">' + overlayContent + '</div></div></div>');
    $('.width-plus', this.domNode).click($.proxy(this,'widthPlus'));
    $('.width-minus', this.domNode).click($.proxy(this,'widthMinus'));
    $('.cancel', this.domNode).click($.proxy(this, 'resizeCancel'));
    $('.save', this.domNode).click($.proxy(this, 'saveManifest'));
    return this;
  };

  Tiles.prototype.widthPlus = function(e) {
    var manifest = this.regionManifest();
    var tile_index = manifest.blockIndex[this.module + '-' + this.delta];
    var tile_width = this.width;
    var steps = Drupal.settings.tiles.stepsKeys;
    var step_index = $.inArray(tile_width, steps);
    var new_width = steps[step_index + 1];

    if (new_width > steps[-1]) {
      alert('This tile is already full width.');
      return false;
    }

    manifest.blocks[tile_index].width = new_width;
    this.requestRegion(manifest, $.proxy(function() {
      $("[data-module='" + this.module + "'][data-delta='" + this.delta + "'] " + this.selector.resizeLink).click();
    }, this));

    return false;
  };

  Tiles.prototype.widthMinus = function(e) {
    var manifest = this.regionManifest();
    var tile_index = manifest.blockIndex[this.module + '-' + this.delta];
    var tile_width = this.width;
    var steps = Drupal.settings.tiles.stepsKeys;
    var step_index = $.inArray(tile_width, steps);
    var new_width = steps[step_index - 1];

    if (new_width < steps[0]) {
      alert('This tile is already at the minimum width.');
      return false;
    }

    manifest.blocks[tile_index].width = new_width;
    this.requestRegion(manifest, $.proxy(function() {
      $("[data-module='" + this.module + "'][data-delta='" + this.delta + "'] " + this.selector.resizeLink).click();
    }, this));

    return false;
  };

  Tiles.prototype.removeResizeOverlay = function() {
    $('.tile-overlay', this.domNode).remove();
    return this;
  };

  Tiles.prototype.resizeCancel = function(e) {
    window.location.reload();
  };

}(jQuery));
