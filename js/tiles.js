(function ($) {
  Drupal.behaviors.tiles = {
    attach: function(context, settings) {
      $(Tile.prototype.selector.moveLink, context).click(function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(e.target).blur();
        if ($(e.target).closest(Tile.prototype.selector.tile).hasClass('dragging')) {
          return;
        }
        block = new Tile(e.target);
        block.setDraggable();
      });

      $(Tile.prototype.selector.resizeLink, context).once('block-width', function() {
        $(this).click(function(e) {
          e.preventDefault();
          e.stopPropagation();
          $(e.target).blur();
          if ($(e.target).closest(Tile.prototype.selector.tile).hasClass('dragging')) {
            return;
          }
          block = new Tile(e.target);
          block.setResizable();
        });
      });

      $(Tile.prototype.selector.offsetLink, context).once('block-offset', function() {
        $(this).click(function(e) {
          e.preventDefault();
          e.stopPropagation();
          $(e.target).blur();
          if ($(e.target).closest(Tile.prototype.selector.tile).hasClass('dragging')) {
            return;
          }
          block = new Tile(e.target);
          block.setOffset();
        });
      });

      $(Tile.prototype.selector.visibilityLink, context).once('block-visibility', function() {
        $(this).click(function(e) {
          e.preventDefault();
          e.stopPropagation();
          $(e.target).blur();
          if ($(e.target).closest(Tile.prototype.selector.tile).hasClass('dragging')) {
            return;
          }
          block = new Tile(e.target);
          block.setVisibility();
        });
      });
    }
  };

  /**
   * @class Tile
   *   Encapsulate js functionality for a tile.
   */

  Tile = function(domNode) {
    // @todo: this should really happen elsewhere, but not sure where else since
    // Drupal.settings needs to be fully populated.
    this.selector.region = Drupal.settings.tiles.typeSelectors;

    $d = $(domNode);
    this.domNode = $d.attr('data-type') === 'block' ? $d : $d.closest(this.selector.tile);
    // Close the contextual links.
    this.domNode.closest('.contextual-links-region').mouseleave();
    this.region = this.domNode.closest(this.selector.region);
    this.region.tiles = $(this.selector.tile, this.region);
    this.module = this.domNode.attr('data-module');
    this.delta = this.domNode.attr('data-delta');
    this.width = parseInt(this.domNode.attr('data-width'), 10);;
    this.offset = parseInt(this.domNode.attr('data-offset'), 10);;
    this.breakpoints = [];
    for (var key in Drupal.settings.tiles.breakpoints) {
      this.breakpoints[key] = parseInt(this.domNode.attr('data-width-' + key), 10);
    }
  };

  Tile.prototype.selector = {
    tile: '.tile',
    region: '',
    moveLink: '.contextual-links .block-arrange a',
    resizeLink: '.contextual-links .block-set-width a',
    offsetLink: '.contextual-links .block-set-offset a',
    visibilityLink: '.contextual-links .block-set-visibility a'
  };

  Tile.prototype.setDraggable = function() {
    this.domNode.addClass('dragging');
    this.region.addClass('dragging');
    $('body').addClass('dragging');
    this.addMoveOverlay();
    this.addRegionOverlays();
    return this;
  };

  Tile.prototype.setOffset = function() {
    this.domNode.addClass('dragging');
    this.region.addClass('dragging');
    $('body').addClass('dragging');
    this.addOffsetOverlay();
    this.addRegionOverlays();
    return this;
  };

  Tile.prototype.unsetDraggable = function() {
    this.domNode.removeClass('dragging');
    this.region.removeClass('dragging');
    $('body').removeClass('dragging');
    this.removeMoveOverlay();
    this.removeRegionOverlays();
    return this;
  };

  Tile.prototype.unsetOffset = function() {
    this.domNode.removeClass('dragging');
    this.region.removeClass('dragging');
    $('body').removeClass('dragging');
    this.removeOffsetOverlay();
    this.removeRegionOverlays();
    return this;
  };

  Tile.prototype.setInProgress = function() {
    this.domNode.addClass('in-progress');
    return this;
  };

  Tile.prototype.unsetInProgress = function() {
    this.domNode.removeClass('in-progress');
    return this;
  };

  /**
   * TODO Use jQuery template
   */
  Tile.prototype.addRegionOverlays = function() {
    // Prevent irresponsible js plugins (twitter I'm looking at you) from using
    // document.write after a block is moved. Using document.write after a page
    // load overwrites the whole dom.
    document.write = function() {};
    $(this.selector.region).each(function(i, el) {
      if (!$(el).children('.region-overlay').length) {
        var friendlyName = 'Unnamed region';
        var gridMarkup = `
          <div class="row">
            <div class="col col-xs-1 col-sm-1 col-md-1 col-lg-1"><div class="column"></div></div>
            <div class="col col-xs-1 col-sm-1 col-md-1 col-lg-1"><div class="column"></div></div>
            <div class="col col-xs-1 col-sm-1 col-md-1 col-lg-1"><div class="column"></div></div>
            <div class="col col-xs-1 col-sm-1 col-md-1 col-lg-1"><div class="column"></div></div>
            <div class="col col-xs-1 col-sm-1 col-md-1 col-lg-1"><div class="column"></div></div>
            <div class="col col-xs-1 col-sm-1 col-md-1 col-lg-1"><div class="column"></div></div>
            <div class="col col-xs-1 col-sm-1 col-md-1 col-lg-1"><div class="column"></div></div>
            <div class="col col-xs-1 col-sm-1 col-md-1 col-lg-1"><div class="column"></div></div>
            <div class="col col-xs-1 col-sm-1 col-md-1 col-lg-1"><div class="column"></div></div>
            <div class="col col-xs-1 col-sm-1 col-md-1 col-lg-1"><div class="column"></div></div>
            <div class="col col-xs-1 col-sm-1 col-md-1 col-lg-1"><div class="column"></div></div>
            <div class="col col-xs-1 col-sm-1 col-md-1 col-lg-1"><div class="column"></div></div>
          </div>
        `;
        if (typeof($(el).attr('data-name-friendly')) !== 'undefined') {
          friendlyName = $(el).attr('data-name-friendly');
        } else if (typeof($(el).attr('data-name')) !== 'undefined') {
          friendlyName = $(el).attr('data-name');
        }
        $(el).append('<div class="region-name">' + friendlyName + '</div><div class="region-grid">' + gridMarkup + '</div><div class="region-overlay"><div class="inner"></div></div>');
      }
    });
    return this;
  };

  Tile.prototype.addMoveOverlay = function() {
    // Prevent irresponsible js plugins (twitter I'm looking at you) from using
    // document.write after a block is moved. Using document.write after a page
    // load overwrites the whole dom.
    document.write = function() {};

    var overlayContent = '<button class="move-left">Left</button>';
    overlayContent += '<button class="move-right">Right</button>';
    overlayContent += '<button class="save">Save</button>';
    overlayContent += '<span class="cancel">Cancel</span>';
    this.region.tiles.prepend('<div class="tile-offset"></div>');
    this.domNode.prepend('<div class="tile-overlay"><div class="inner"><div class="control-wrapper">' + overlayContent + '</div></div></div>');
    $('.move-left', this.domNode).click($.proxy(this,'moveLeft'));
    $('.move-right', this.domNode).click($.proxy(this,'moveRight'));
    $('.cancel', this.domNode).click($.proxy(this, 'moveCancel'));
    $('.save', this.domNode).click($.proxy(this, 'saveManifest'));
    return this;
  };

  Tile.prototype.removeMoveOverlay = function() {
    $('.tile-offset', this.region.tiles).remove();
    $('.tile-overlay', this.domNode).remove();
    return this;
  };

  Tile.prototype.removeRegionOverlays = function() {
    $('.region-overlay', $(this.selector.region)).remove();
    $('.region-name', $(this.selector.region)).remove();
    $('.region-grid', $(this.selector.region)).remove();
    return this;
  };

  Tile.prototype.moveLeft = function(e) {
    var manifest = this.regionManifest();
    if (manifest.blocks[0].module === this.module &&
        manifest.blocks[0].delta === this.delta) {
      alert('This is already the first block in this region.');
      return false;
    }
    this.setInProgress();
    var tile_index = manifest.blockIndex[this.module + '-' + this.delta];
    var prev_tile_index = tile_index - 1;
    var tile_weight = manifest.blocks[tile_index].weight;
    manifest.blocks[tile_index].weight = manifest.blocks[prev_tile_index].weight;
    manifest.blocks[prev_tile_index].weight = tile_weight;
    this.requestRegion(manifest, $.proxy(function() {
      $("[data-module='" + this.module + "'][data-delta='" + this.delta + "'] " + this.selector.moveLink  + ':eq(0)').click();
    }, this));
    return false;
  };

  Tile.prototype.moveRight = function(e) {
    var manifest = this.regionManifest();
    if (manifest.blocks[manifest.blocks.length-1].module === this.module &&
        manifest.blocks[manifest.blocks.length-1].delta === this.delta) {
      alert('This is already the last block in this region.');
      return false;
    }
    this.setInProgress();
    var tile_index = manifest.blockIndex[this.module + '-' + this.delta];
    var next_tile_index = tile_index + 1;
    var tile_weight = manifest.blocks[tile_index].weight;
    manifest.blocks[tile_index].weight = manifest.blocks[next_tile_index].weight;
    manifest.blocks[next_tile_index].weight = tile_weight;
    this.requestRegion(manifest, $.proxy(function() {
      $("[data-module='" + this.module + "'][data-delta='" + this.delta + "'] " + this.selector.moveLink  + ':eq(0)').click();
    }, this));
    return false;
  };

  Tile.prototype.moveCancel = function(e) {
    window.location.reload();
  };

  Tile.prototype.addOffsetOverlay = function() {
    // Prevent irresponsible js plugins (twitter I'm looking at you) from using
    // document.write after a block is moved. Using document.write after a page
    // load overwrites the whole dom.
    document.write = function() {};

    var steps = Drupal.settings.tiles.steps;
    steps[0] = '0%';

    var overlayContent = '<select class="offset-menu">';
    for (var i = 0; i <= Drupal.settings.tiles.stepsKeys.length; i++ ) {
      var selected = (this.offset == i) ? ' selected' : '';
      overlayContent += '<option value="' + i + '"' + selected + '>' + steps[i] + '</option>';
    }

    overlayContent += '</select>';
    overlayContent += '<button class="move-left">Left</button>';
    overlayContent += '<button class="move-right">Right</button>';
    overlayContent += '<button class="save">Save</button>';
    overlayContent += '<span class="cancel">Cancel</span>';
    this.region.tiles.prepend('<div class="tile-offset"></div>');
    this.domNode.prepend('<div class="tile-overlay"><div class="inner"><div class="control-wrapper">' + overlayContent + '</div></div></div>');
    $('select.offset-menu', this.domNode).change($.proxy(this, 'offsetSelect'));
    $('.move-left', this.domNode).click($.proxy(this,'offsetLeft'));
    $('.move-right', this.domNode).click($.proxy(this,'offsetRight'));
    $('.cancel', this.domNode).click($.proxy(this, 'offsetCancel'));
    $('.save', this.domNode).click($.proxy(this, 'saveManifest'));
    return this;
  };

  Tile.prototype.removeOffsetOverlay = function() {
    $('.tile-offset', this.region.tiles).remove();
    $('.tile-overlay', this.domNode).remove();
    return this;
  };

  Tile.prototype.offsetSelect = function(e) {
    var manifest = this.regionManifest();
    var tile_index = manifest.blockIndex[this.module + '-' + this.delta];
    var tile_offset = this.offset;
    var new_offset = $('select option:selected', this.domNode).val();

    if (new_offset === undefined) {
      alert('This tile is already at the minimum offset.');
      return false;
    }

    this.setInProgress();
    manifest.blocks[tile_index].offset = new_offset;

    // Set all breakpoints that aren't set to hidden to new offset. This should
    // be altered once tiles has the ability to set the offset on
    // a per-breakpoint basis.
    // for (var key in Drupal.settings.tiles.breakpoints) {
    //   if (manifest.blocks[tile_index].breakpoints[key] != 0) {
    //     manifest.blocks[tile_index].breakpoints[key] = new_offset;
    //   }
    // }

    this.requestRegion(manifest, $.proxy(function() {
      $("[data-module='" + this.module + "'][data-delta='" + this.delta + "'] " + this.selector.offsetLink + ':eq(0)').click();
    }, this));

    return false;
  };

  Tile.prototype.offsetLeft = function(e) {
    var manifest = this.regionManifest();
    var tile_index = manifest.blockIndex[this.module + '-' + this.delta];
    var tile_offset = this.offset;
    var steps = Drupal.settings.tiles.stepsKeys.slice();
    steps.unshift(0);
    var step_index = $.inArray(tile_offset, steps);
    var new_offset = steps[step_index - 1];

    if (new_offset === undefined) {
      alert('This tile is already at the minimum offset.');
      return false;
    }

    this.setInProgress();
    manifest.blocks[tile_index].offset = new_offset;

    // Set all breakpoints that aren't set to hidden to new offset. This should
    // be altered once tiles has the ability to set the offset on
    // a per-breakpoint basis.
    // for (var key in Drupal.settings.tiles.breakpoints) {
    //   if (manifest.blocks[tile_index].breakpoints[key] != 0) {
    //     manifest.blocks[tile_index].breakpoints[key] = new_offset;
    //   }
    // }

    this.requestRegion(manifest, $.proxy(function() {
      $("[data-module='" + this.module + "'][data-delta='" + this.delta + "'] " + this.selector.offsetLink + ':eq(0)').click();
    }, this));

    return false;
  };

  Tile.prototype.offsetRight = function(e) {
    var manifest = this.regionManifest();
    var tile_index = manifest.blockIndex[this.module + '-' + this.delta];
    var tile_offset = this.offset;
    var steps = Drupal.settings.tiles.stepsKeys;
    var step_index = $.inArray(tile_offset, steps);
    var new_offset = steps[step_index + 1];

    if (new_offset === undefined) {
      alert('This tile is already full offset.');
      return false;
    }

    this.setInProgress();
    manifest.blocks[tile_index].offset = new_offset;

    // Set all breakpoints that aren't set to hidden to new offset. This should
    // be altered once tiles has the ability to set the offset on
    // a per-breakpoint basis.
    // for (var key in Drupal.settings.tiles.breakpoints) {
    //   if (manifest.blocks[tile_index].breakpoints[key] != 0) {
    //     manifest.blocks[tile_index].breakpoints[key] = new_offset;
    //   }
    // }

    this.requestRegion(manifest, $.proxy(function() {
      $("[data-module='" + this.module + "'][data-delta='" + this.delta + "'] " + this.selector.offsetLink + ':eq(0)').click();
    }, this));

    return false;
  };

  Tile.prototype.offsetCancel = function(e) {
    window.location.reload();
  };

  Tile.prototype.regionManifest = function() {
    var region = this.region.attr('data-name');
    var manifest = {
      region: region,
      selector: this.region.attr('data-tiles-selector') ? this.region.attr('data-tiles-selector') : Drupal.settings.tiles.selector,
      type: this.region.attr('data-type'),
      blockIndex: {},
      blocks: []
    };
    var that = this;
    var weight = 0;
    $(this.selector.tile, this.region).each(function(i) {
      var $t = $(this);
      var module = $t.attr('data-module');
      var delta = $t.attr('data-delta');
      if ($t.closest(that.selector.region)[0] !== that.region[0]) {
        return;
      }
      manifest.blockIndex[module + '-' + delta] = weight;
      var block = {
        module: module,
        delta: delta,
        region: region,
        width: parseInt($t.attr('data-width'), 10),
        offset: parseInt($t.attr('data-offset'), 10),
        weight: weight
      }
      block.breakpoints = {};
      for (var key in Drupal.settings.tiles.breakpoints) {
        block.breakpoints[key] = parseInt($t.attr('data-width-' + key), 10);
      }
      manifest.blocks.push(block);
      weight++;
    });
    return manifest;
  };

  Tile.prototype.requestRegion = function(manifest, callback) {
    $.ajax({
      type: 'POST',
      url: window.location.toString(),
      data: JSON.stringify(manifest),
      dataType: 'html',
      beforeSend: function(xhr) {
        xhr.setRequestHeader('X-TILES', manifest.type);
      },
      success: $.proxy(function(data, textStatus, jqXHR) {
        this.handleRequestRegionSuccess(data, textStatus, jqXHR);
        callback(data, textStatus, jqXHR);
      }, this),
      error: $.proxy(this, 'handleRequestRegionError')
    });
  };

  Tile.prototype.handleRequestRegionSuccess = function(data, textStatus, jqXHR) {
    this.region.html(data);
    $(document).trigger('tiles.requestSuccess', this);
    Drupal.attachBehaviors(this.region, Drupal.settings);
  };

  Tile.prototype.handleRequestRegionError = function(jqXHR, textStatus, errorThrown) {
    this.unsetInProgress();
    $(document).trigger('tiles.requestError', this);
    alert("There was an error changing this tile.");
  };

  Tile.prototype.saveManifest = function() {
    var manifest = this.regionManifest();
    $.ajax({
      type: 'POST',
      url: '/admin/tiles-save-tiles',
      data: JSON.stringify(manifest),
      dataType: 'json',
      beforeSend: function(xhr) {
        xhr.setRequestHeader('X-TILES', manifest.type);
      },
      success: $.proxy(this.saveHandleSuccess, this),
      error: $.proxy(this.saveHandleError, this)
    });
    return false;
  };

  Tile.prototype.saveHandleSuccess = function() {
    this.unsetDraggable();
    this.unsetResizable();
    this.unsetOffset();
  };

  Tile.prototype.saveHandleError = function() {
    alert('Sorry, there was a problem saving the updated layout. Please try again after the page reloads.');
    window.location.reload();
  };

  Tile.prototype.setResizable = function() {
    this.domNode.addClass('resizing');
    this.region.addClass('resizing');
    $('body').addClass('resizing');
    this.addResizeOverlay();
    this.addRegionOverlays();
    return this;
  };

  Tile.prototype.unsetResizable = function() {
    this.domNode.removeClass('resizing');
    this.region.removeClass('resizing');
    $('body').removeClass('resizing');
    this.removeResizeOverlay();
    this.removeRegionOverlays();
    return this;
  };

  Tile.prototype.setVisibility = function() {
    this.domNode.addClass('resizing');
    $('body').addClass('resizing');
    this.addVisibilityOverlay();
    return this;
  };

  Tile.prototype.unsetVisibility = function() {
    this.domNode.removeClass('resizing');
    $('body').removeClass('resizing');
    this.removeVisibilityOverlay();
    return this;
  };

  /**
   * TODO Use jQuery template
   */
  Tile.prototype.addResizeOverlay = function() {
    // Prevent irresponsible js plugins (twitter I'm looking at you) from using
    // document.write after a block is moved. Using document.write after a page
    // load overwrites the whole dom.
    document.write = function() {};

    var overlayContent = '<select class="width-menu">';
    for (var i = 1; i <= Drupal.settings.tiles.stepsKeys.length; i++ ) {
      var selected = (this.width == i) ? ' selected' : '';
      overlayContent += '<option value="' + i + '"' + selected + '>' + Drupal.settings.tiles.steps[i] + '</option>';
    }
    overlayContent += '</select>';
    overlayContent += '<button class="width-minus">-</button>';
    overlayContent += '<button class="width-plus">+</button>';
    overlayContent += '<div class="save-cancel-wrapper">';
    overlayContent += '<button class="save">Save</button>';
    overlayContent += '<span class="cancel">Cancel</span>';
    overlayContent += '</div>';
    this.region.tiles.prepend('<div class="tile-offset"></div>');
    this.domNode.prepend('<div class="tile-overlay"><div class="inner"><div class="control-wrapper">' + overlayContent + '</div></div></div>');
    $('select.width-menu', this.domNode).change($.proxy(this, 'widthSelect'));
    $('.width-plus', this.domNode).click($.proxy(this,'widthPlus'));
    $('.width-minus', this.domNode).click($.proxy(this,'widthMinus'));
    $('.cancel', this.domNode).click($.proxy(this, 'resizeCancel'));
    $('.save', this.domNode).click($.proxy(this, 'saveManifest'));
    return this;
  };

  Tile.prototype.addVisibilityOverlay = function() {
    // Prevent irresponsible js plugins (twitter I'm looking at you) from using
    // document.write after a block is moved. Using document.write after a page
    // load overwrites the whole dom.
    document.write = function() {};

    var overlayContent = '<div class="visibility-options">';

    for (var key in Drupal.settings.tiles.breakpoints) {
      var checked = (this.breakpoints[key] > 0) ? ' checked' : '';
      overlayContent += '<div class="checkbox">';
      overlayContent += '<label>';
      overlayContent += '<input name="' + key + '" id="breakpoint-' + key + '" type="checkbox" class="visibility"' + checked + '>';
      overlayContent += Drupal.settings.tiles.breakpoints[key] + '</label>';
      overlayContent += '</div>';
    }

    overlayContent += '</div>';
    overlayContent += '<div class="save-cancel-wrapper">';
    overlayContent += '<button class="save">Save</button>';
    overlayContent += '<span class="cancel">Cancel</span>';
    overlayContent += '</div>';
    this.region.tiles.prepend('<div class="tile-offset"></div>');
    this.domNode.prepend('<div class="tile-overlay"><div class="inner"><div class="control-wrapper">' + overlayContent + '</div></div></div>');
    $('.visibility', this.domNode).change($.proxy(this, 'visibilitySelect'));
    $('.cancel', this.domNode).click($.proxy(this, 'resizeCancel'));
    $('.save', this.domNode).click($.proxy(this, 'saveVisibility'));
    return this;
  };

  Tile.prototype.widthPlus = function(e) {
    var manifest = this.regionManifest();
    var tile_index = manifest.blockIndex[this.module + '-' + this.delta];
    var tile_width = this.width;
    var steps = Drupal.settings.tiles.stepsKeys;
    var step_index = $.inArray(tile_width, steps);
    var new_width = steps[step_index + 1];

    if (new_width === undefined) {
      alert('This tile is already full width.');
      return false;
    }

    this.setInProgress();
    manifest.blocks[tile_index].width = new_width;

    // Set all breakpoints that aren't set to hidden to new width. This should
    // be altered once tiles has the ability to set the width on
    // a per-breakpoint basis.
    for (var key in Drupal.settings.tiles.breakpoints) {
      if (manifest.blocks[tile_index].breakpoints[key] != 0) {
        manifest.blocks[tile_index].breakpoints[key] = new_width;
      }
    }

    this.requestRegion(manifest, $.proxy(function() {
      $("[data-module='" + this.module + "'][data-delta='" + this.delta + "'] " + this.selector.resizeLink + ':eq(0)').click();
    }, this));

    return false;
  };

  Tile.prototype.widthMinus = function(e) {
    var manifest = this.regionManifest();
    var tile_index = manifest.blockIndex[this.module + '-' + this.delta];
    var tile_width = this.width;
    var steps = Drupal.settings.tiles.stepsKeys;
    var step_index = $.inArray(tile_width, steps);
    var new_width = steps[step_index - 1];

    if (new_width === undefined) {
      alert('This tile is already at the minimum width.');
      return false;
    }

    this.setInProgress();
    manifest.blocks[tile_index].width = new_width;

    // Set all breakpoints that aren't set to hidden to new width. This should
    // be altered once tiles has the ability to set the width on
    // a per-breakpoint basis.
    for (var key in Drupal.settings.tiles.breakpoints) {
      if (manifest.blocks[tile_index].breakpoints[key] != 0) {
        manifest.blocks[tile_index].breakpoints[key] = new_width;
      }
    }

    this.requestRegion(manifest, $.proxy(function() {
      $("[data-module='" + this.module + "'][data-delta='" + this.delta + "'] " + this.selector.resizeLink + ':eq(0)').click();
    }, this));

    return false;
  };

  Tile.prototype.widthSelect = function(e) {
    var manifest = this.regionManifest();
    var tile_index = manifest.blockIndex[this.module + '-' + this.delta];
    var tile_width = this.width;
    var steps = Drupal.settings.tiles.stepsKeys;
    var step_index = $.inArray(tile_width, steps);
    var new_width = $('select option:selected', this.domNode).val();

    if (new_width === undefined) {
      alert('undefined width'); // DO NOT LEAVE THIS AS-IS
      return false;
    }

    this.setInProgress();
    manifest.blocks[tile_index].width = new_width;

    // Set all breakpoints that aren't set to hidden to new width. This should
    // be altered once tiles has the ability to set the width on
    // a per-breakpoint basis.
    for (var key in Drupal.settings.tiles.breakpoints) {
      if (manifest.blocks[tile_index].breakpoints[key] != 0) {
        manifest.blocks[tile_index].breakpoints[key] = new_width;
      }
    }

    this.requestRegion(manifest, $.proxy(function() {
      $("[data-module='" + this.module + "'][data-delta='" + this.delta + "'] " + this.selector.resizeLink + ':eq(0)').click();
    }, this));
  };

  Tile.prototype.visibilitySelect = function(e) {
    for (var key in Drupal.settings.tiles.breakpoints) {
      if (!$('input[name="' + key + '"]', this.domNode).is(':checked')) {
        this.domNode.attr('data-width-' + key, 0);
      }
      else {
        this.domNode.attr('data-width-' + key, this.domNode.attr('data-width'));
      }
    }
  };

  Tile.prototype.saveVisibility = function(e) {
    this.requestRegion(this.regionManifest());
    this.saveManifest();
  }

  Tile.prototype.removeResizeOverlay = function() {
    $('.tile-offset', this.region.tiles).remove();
    $('.tile-overlay', this.domNode).remove();
    return this;
  };

  Tile.prototype.resizeCancel = function(e) {
    window.location.reload();
  };

}(jQuery));
