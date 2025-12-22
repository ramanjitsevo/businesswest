(function($) {
	"use strict";

	UNCODE.animateSubInside = function( _this, remove ){
	if ( typeof remove !== 'undefined' && remove === true ) {
		$('.animate_when_almost_visible', _this).each(function(){
			$(this).removeClass('start_animation');
		});
		$(document).trigger('sub-menu-slide-up');
	} else {
		$.each($('.animate_when_almost_visible:not(.start_animation):not(.t-inside):not(.drop-image-separator), .index-scroll .animate_when_almost_visible, .tmb-media .animate_when_almost_visible:not(.start_animation), .animate_when_almost_visible.has-rotating-text, .custom-grid-container .animate_when_almost_visible:not(.start_animation)', _this), function(index, val) {
			var element = $(val),
				delayAttr = element.attr('data-delay');
			if (delayAttr == undefined) delayAttr = 0;
			requestTimeout(function() {
				element.addClass('start_animation');
			}, delayAttr);
		});
		document.dispatchEvent(new CustomEvent('sub-menu-slide-down'));
	}
};


UNCODE.menuSystem = function() {

	function menuMobile() {
		var $body = $('body'),
			scrolltop,
			$mobileToggleButton = $('.mobile-menu-button, .uncode-close-offcanvas-mobile:not(.vc_element)'),
			$masthead = $('#masthead'),
			$menuBlock = $('#uncode-mobile-menu-block'),
			$box,
			$el,
			$el_transp,
			elHeight,
			offCanvasAnim,
			check,
			animating = false,
			stickyMobile = false,
			menuClose = new CustomEvent('menuMobileClose'),
			menuOpen = new CustomEvent('menuMobileOpen');
		UNCODE.menuOpened = false;
		$mobileToggleButton.on('click', function(event) {
			event.stopPropagation();
			var btn = this;
			if ($(btn).hasClass('overlay-close')) return;
			event.preventDefault();
			$('.overlay-search.open .menu-close-dd', $masthead).trigger('click');
			if (UNCODE.wwidth <= UNCODE.mediaQuery) {
				$box = $(this).closest('.box-container').find('.main-menu-container:not(.no-mobile):not(.main-wrapper *)');
				$el = $(this).closest('.box-container').find('.menu-horizontal-inner:not(.row-brand):not(.main-wrapper *), .menu-sidebar-inner:not(.main-wrapper *)');
				$el_transp = $('.menu-absolute.menu-transparent');
				if (UNCODE.isMobile) {
					if ( $('.menu-wrapper.menu-sticky, .menu-wrapper.menu-hide-only, .main-header .menu-sticky-vertical, .main-header .menu-hide-only-vertical, .menu-mobile-centered, .menu-sticky-mobile').length ) {
						stickyMobile = true;
						elHeight = window.innerHeight - UNCODE.menuMobileHeight - (UNCODE.bodyBorder * 2) - UNCODE.adminBarHeight + 1;
					} else {
						elHeight = 0;
						$.each($box.find('> div'), function(index, val) {
							elHeight += $(val).outerHeight();
						});
					}
				} else {
					elHeight = 0;
					$.each($el, function(index, val) {
						elHeight += $(val).outerHeight();
					});
				}
				var open = function() {
					clearTimeout(offCanvasAnim);
					if (!animating) {
						$body.addClass('open-overlay-menu').addClass('opening-overlay-menu');
						scrolltop = $(window).scrollTop();
						window.dispatchEvent(menuOpen);
						animating = true;
						UNCODE.menuOpened = true;
						if ($('body[class*="vmenu-"], body.hmenu-center').length && ($('.menu-hide, .menu-sticky, .menu-transparent').length)) {
							if ( $body.hasClass('menu-sticky-mobile') || ( $('#masthead.menu-transparent').length && !UNCODE.isMobile ) ) {
								$('.main-header > .vmenu-container').css({position:'fixed', top: ($('.menu-container').outerHeight() + UNCODE.bodyBorder + UNCODE.adminBarHeight) + 'px'});
							}
							if ($('body.menu-offcanvas').length) {
								$('.menu-container:not(.sticky-element):not(.grid-filters)').css({position:'fixed'});
								$('.vmenu-container.menu-container:not(.sticky-element):not(.grid-filters)').css({position:'fixed', top: (UNCODE.menuMobileHeight + UNCODE.bodyBorder + UNCODE.adminBarHeight) + 'px'});
							} else {
								if ( $('.menu-hide, .menu-sticky').length ) {
									if ( UNCODE.wwidth >= 960 && $('.menu-sticky').length  ) {
										$('.menu-container:not(.sticky-element):not(.grid-filters)').css({position:'fixed'});
									}
								}
							}
						}
						if ($('body.hmenu-center').length && ( (!UNCODE.isMobile && $('.menu-hide, .menu-sticky').length) || (UNCODE.isMobile && $('.menu-sticky-mobile').length) )) {
							//$("#masthead")[0].scrollIntoView();
							$('.menu-container:not(.sticky-element):not(.grid-filters)').css({position:'fixed', top: (UNCODE.menuMobileHeight + UNCODE.bodyBorder + UNCODE.adminBarHeight) + 'px'});
						}
						$box.addClass('open-items');
						if ($el_transp.length && $('body.menu-mobile-transparent').length) {
							$el_transp.addClass('is_mobile_open');
						}
						if ( ! $('body').hasClass('menu-mobile-off-canvas') ) {
							btn.classList.add('close');
							$box.animate({
								height: elHeight
							}, 600, "easeInOutCirc", function() {
								animating = false;
								if (!stickyMobile) $box.css('height', 'auto');
							});
						} else {
							animating = false;
						}

						if ( $menuBlock.length ) {
							UNCODE.animateSubInside($menuBlock);
						}
					}
				};

				var close = function() {
					clearTimeout(offCanvasAnim);
					if (!animating) {
						window.dispatchEvent(menuClose);
						animating = true;
						UNCODE.menuOpened = false;
						if ( ! $('body').hasClass('menu-mobile-off-canvas') ) {
							btn.classList.remove('close');
							btn.classList.add('closing');
						}
						$box.addClass('close');
						requestTimeout(function() {
							$box.removeClass('close');
							$box.removeClass('open-items');
							btn.classList.remove('closing');
							if ($el_transp.length) {
								$el_transp.removeClass('is_mobile_open');
							}
						}, 500);
						$body.removeClass('opening-overlay-menu');
						if ( ! $('body').hasClass('menu-mobile-off-canvas') ) {
							$box.animate({
								height: 0
							}, {
								duration: 600,
								easing: "easeInOutCirc",
								complete: function(elements) {
									$(elements).css('height', '');
									animating = false;
									if ($('body[class*="vmenu-"]').length && UNCODE.wwidth >= 960) {
										$('.main-header > .vmenu-container').add('.menu-container:not(.sticky-element):not(.grid-filters)').css('position','relative');
									}
									$body.removeClass('open-overlay-menu');
									if ( $menuBlock.length ) {
										UNCODE.animateSubInside($menuBlock, true);
									}
								}
							});
						} else {
							animating = false;
							offCanvasAnim = setTimeout(function(){
								$body.removeClass('open-overlay-menu');
								if ( $menuBlock.length ) {
									UNCODE.animateSubInside($menuBlock, true);
								}
							}, 1000);
						}
					}
				};
				check = (!UNCODE.menuOpened) ? open() : close();
			}
		});

		$('html').on('click', function(event){
			if ( $('body').hasClass('menu-mobile-off-canvas') && UNCODE.wwidth < 960 && UNCODE.menuOpened && event.clientX < SiteParameters.menu_mobile_offcanvas_gap ) {
				$('.uncode-close-offcanvas-mobile:visible').trigger('click');
			}
		});

		window.addEventListener('menuMobileTrigged', function(e) {
			$('.mobile-menu-button.close, .opening-overlay-menu .uncode-close-offcanvas-mobile').trigger('click');
		});
		window.addEventListener('orientationchange', function(e) {
			$('#logo-container-mobile .mobile-menu-button.close').trigger('click');
		});
		window.addEventListener("resize", function() {
			if ($(window).width() < UNCODE.mediaQuery) {
				if (UNCODE.isMobile) {
					var $box = $('.box-container .main-menu-container'),
						$el = $('.box-container .menu-horizontal-inner:not(.no-mobile), .box-container .menu-sidebar-inner:not(.no-mobile)');
					if ($($box).length && $($box).hasClass('open-items') && $($box).css('height') != 'auto' && ! $('body').hasClass('menu-mobile-off-canvas') ) {
						if ($('.menu-wrapper.menu-sticky, .menu-wrapper.menu-hide-only').length) {
							elHeight = 0;
							$.each($el, function(index, val) {
								elHeight += $(val).outerHeight();
							});
							elHeight = window.innerHeight - $('.menu-wrapper.menu-sticky .menu-container .row-menu-inner, .menu-wrapper.menu-hide-only .menu-container .row-menu-inner').height() - (UNCODE.bodyBorder * 2) + 1;
							$($box).css('height', elHeight + 'px');
						}
					}
				}
			} else {
				$('.menu-hide-vertical').removeAttr('style');
				$('.menu-container-mobile').removeAttr('style');
				$('.vmenu-container.menu-container').removeAttr('style');

				if ( UNCODE.menuOpened ) {
					$('.open-items').removeClass('open-items');
					$('.is_mobile_open').removeClass('is_mobile_open');
					$('.open-overlay-menu').removeClass('open-overlay-menu');
					$('.opening-overlay-menu').removeClass('opening-overlay-menu');
				}
			}
		});

		$(window).on('scroll', function(){
			if ( $body.hasClass('opening-overlay-menu') && $body.hasClass('menu-mobile-off-canvas') && UNCODE.wwidth < 960 ) {
				$(window).scrollTop(scrolltop);
				return false;
			}
		});

	};

	function menuOffCanvas() {
		var menuClose = new CustomEvent('menuCanvasClose'),
			menuOpen = new CustomEvent('menuCanvasOpen');
		$('.menu-primary .menu-button-offcanvas:not(.menu-close-search)').on('click', function(event) {
			if ($(window).width() > UNCODE.mediaQuery) {
				if ( $('body.vmenu-offcanvas-overlay').length ) {
					if ($(event.currentTarget).hasClass('off-close')) {
						$(event.currentTarget).removeClass('off-close');
						requestTimeout(function() {
							window.dispatchEvent(menuClose);
						}, 500);

					} else {
						$(event.currentTarget).addClass('off-close');
						window.dispatchEvent(menuOpen);
					}
				} else {
					if ($(event.currentTarget).hasClass('close')) {
						$(event.currentTarget).removeClass('close');
						$(event.currentTarget).addClass('closing');
						requestTimeout(function() {
							$(event.currentTarget).removeClass('closing');
							window.dispatchEvent(menuClose);
						}, 500);

					} else {
						$(event.currentTarget).addClass('close');
						window.dispatchEvent(menuOpen);
					}
				}
			}

			$('body').toggleClass('off-opened');
		});

		$('body').on('click.menu-off-canvas-mobile', function(e){
			if ( $(window).width() > UNCODE.mediaQuery && $('body.menu-offcanvas.vmenu-offcanvas-overlay.off-opened').length ) {
				var $vMenuCont = $('#masthead .vmenu-container'),
					$close_menu = $('.uncode-close-offcanvas-overlay', $vMenuCont),

					vmenu_h = parseFloat( $vMenuCont.outerHeight() ),
					vmenu_w = parseFloat( $vMenuCont.outerWidth() ),
					vmenu_off = $vMenuCont.offset(),
					vmenu_l = parseFloat(vmenu_off.left),
					vmenu_t = parseFloat(vmenu_off.top),
					vmenu_r = vmenu_l + vmenu_w,
					vmenu_b = vmenu_t + vmenu_h,

					close_h = parseFloat( $close_menu.outerHeight() ),
					close_w = parseFloat( $close_menu.outerWidth() ),
					close_off = $close_menu.offset(),
					close_l = parseFloat(close_off.left),
					close_t = parseFloat(close_off.top),
					close_r = close_l + close_w,
					close_b = close_t + close_h;
				if (
					!(
						e.clientX > vmenu_l &&
						e.clientX < vmenu_r &&
						e.clientY > vmenu_t &&
						e.clientY < vmenu_b
					)
					||
					(
						e.clientX > close_l &&
						e.clientX < close_r &&
						e.clientY > close_t &&
						e.clientY < close_b
					)
				) {
					$('.menu-primary .menu-button-offcanvas:not(.menu-close-search)').trigger('click');
				}
			}
		});
	};

	function menuOverlay() {
		if ( $('.overlay').length ) {
			$('.overlay').removeClass('hidden');
		}
		if ( ($('.overlay-sequential').length > 0 && UNCODE.wwidth >= UNCODE.mediaQuery) || ($('.menu-mobile-animated').length > 0 && UNCODE.wwidth < UNCODE.mediaQuery) ) {
			$('.overlay-sequential .menu-smart > li, .menu-sticky .menu-container .menu-smart > li, .menu-hide.menu-container .menu-smart > li, .vmenu-container .menu-smart > li, .uncode-menu-additional-text').each(function(index, el) {
				var transDelay = (index / 20) + 0.1;
				if ( $('body').hasClass('menu-mobile-centered') && $(window).width() < UNCODE.mediaQuery )
					transDelay = transDelay + 0.3;
				$(this)[0].setAttribute('style', '-webkit-transition-delay:' + transDelay + 's; -moz-transition-delay:' + transDelay + 's; -ms-transition-delay:' + transDelay + 's; -o-transition-delay:' + transDelay + 's; transition-delay:' + transDelay + 's');
			});
		}

	};
	var $secondary_parent;
	function menuAppend() {

		var $menuBlock = $('#uncode-mobile-menu-block').length;
		if ( $menuBlock ) {
			return;
		}
		var $body = $('body'),
			$menuCont = $('.menu-container:not(.vmenu-container)'),
			$vMenuCont = $('.menu-container.vmenu-container'),
			$cta = $('.navbar-cta'),
			$socials = $('.navbar-social:not(.appended-navbar)'),
			$ul = $('.navbar-main ul.menu-primary-inner'),
			$ulCta,
			$ulSocials,
			$navLast,
			$firstMenu = $('.main-menu-container:first-child', $menuCont),
			$secondMenu = $('.main-menu-container:last-child', $menuCont),
			$firstNav = $('.navbar-nav:not(.uncode-close-offcanvas-mobile):first-child', $firstMenu),
			$secondNav = $('.navbar-nav:not(.uncode-close-offcanvas-mobile):first-child', $secondMenu),
			$ulFirst = $('> ul', $firstNav),
			setCTA,
			appendCTA = function(){
				return true;
			},
			appendSocials = function(){
				return true;
			},
			appendSplit = function(){
				return true;
			};

		if ( ( $body.hasClass('menu-offcanvas') || $body.hasClass('menu-overlay') || $body.hasClass('hmenu-center-split') ) && $cta.length ) {
			$ulCta = $('> ul', $cta);
			$ulCta.parent().addClass('mobile-hidden').addClass('tablet-hidden');

			appendCTA = function(){
				if (UNCODE.wwidth < UNCODE.mediaQuery) {
					$ul.after($ulCta);
				} else {
					$cta.append($ulCta);
				}
			}
		}

		if ( ! $body.hasClass('cta-not-appended') ) {
			appendCTA();
		}

		var $smartSocial = $menuCont.add($vMenuCont).find('.menu-smart-social');
		$smartSocial.each(function(){
			var $_smartSocial = $(this);
			$('> li', $_smartSocial).each(function(){
				var $li = $(this);
				if ( $li.hasClass('mobile-hidden') ) {
					$_smartSocial.addClass('mobile-hidden');
				} else {
					$_smartSocial.removeClass('mobile-hidden');
					return false;
				}
			});

			$('> li', $_smartSocial).each(function(){
				var $li = $(this);
				if ( $li.hasClass('tablet-hidden') ) {
					$_smartSocial.addClass('tablet-hidden');
				} else {
					$_smartSocial.removeClass('tablet-hidden');
					return false;
				}
			});
		});

		if ( ( $body.hasClass('hmenu-center-split') || $body.hasClass('menu-overlay-center') || $body.hasClass('menu-offcanvas') || $body.hasClass('vmenu') ) && $socials.length ) {
			$ulSocials = $('> ul', $socials).addClass('menu-smart-social');
			if ( $body.hasClass('hmenu-center-split') ) {
				$navLast = $('.menu-horizontal-inner .navbar-nav-last', $menuCont);
			} else {
				$navLast = $('.navbar-nav-last', $vMenuCont);
			}

			if ( ! $navLast.length ) {
				var _navLast = $('<div class="nav navbar-nav navbar-social navbar-nav-last appended-navbar" />');
				if ( $body.hasClass('hmenu-center-split') ) {
					$('.menu-horizontal-inner', $menuCont).append(_navLast);
					$navLast = $('.menu-horizontal-inner .navbar-nav-last', $menuCont);
				} else {
					$('.menu-sidebar-inner', $vMenuCont).last().append(_navLast);
					$navLast = $('.navbar-nav-last', $vMenuCont);
				}
			}

			appendSocials = function(){
				if ( !$body.hasClass('menu-overlay-center') ) {
				// 	if ( !$navLast.find('ul.menu-smart-social').length ) {
				// 		$ulSocials = $('.menu-smart-social li.social-icon', $vMenuCont);
				// 		$navLast.append('<ul class="menu-smart menu-smart-social" />');
				// 		$ulSocials.each(function(){
				// 			var $li_social = $(this);
				// 			$navLast.find('ul.menu-smart-social').append($li_social);
				// 		});
				// 	}
				// } else {
					if (UNCODE.wwidth < UNCODE.mediaQuery) {
						$socials.addClass('mobile-hidden').addClass('tablet-hidden')
						if ( ! $('> ul.menu-smart-social li', $socials).length ) {
							$('> ul.menu-smart-social li', $socials).remove();
						}
						$navLast.append($ulSocials);
					} else {
						if ( ! $('> ul.menu-smart-social li', $navLast).length ) {
							$('> ul.menu-smart-social li', $navLast).remove();
						}
						$socials.append($ulSocials);
					}
				}
			}
			appendSocials();
		}

		if ( $vMenuCont.length ) {
			var $accordion_secondary = $('.menu-accordion-secondary', $vMenuCont);
		} else {
			var $accordion_secondary = $('.menu-accordion-secondary', $menuCont);
		}
		if ( $accordion_secondary.length ) {
			var $accordion_secondary_ph = $vMenuCont.add($menuCont).find('.accordion-secondary-ph');
			if (UNCODE.wwidth < UNCODE.mediaQuery) {
				if ( !$accordion_secondary_ph.length ) {
					$accordion_secondary.after('<span class="accordion-secondary-ph" />');
				}
				if ( $vMenuCont.length ) {
					$('.menu-sidebar-inner', $vMenuCont).first().find('.menu-accordion:not(.menu-accordion-secondary):not(.menu-accordion-extra-icons)').last().after($accordion_secondary);
				} else {
					if ( $('.navbar-nav.navbar-cta:not(.mobile-hidden)', $menuCont).length ) {
						$('.navbar-nav.navbar-cta', $menuCont).after($accordion_secondary);
					} else {
						$('.navbar-nav.navbar-main', $menuCont).after($accordion_secondary);
					}
				}
			} else {
				if ( typeof $accordion_secondary_ph !== 'undefined' && $accordion_secondary_ph.length ) {
					$accordion_secondary_ph.before($accordion_secondary);
				}
			}
		}

		if ( $vMenuCont.length ) {
			var $extra_icons = $('.menu-accordion-extra-icons', $vMenuCont);
		} else {
			var $extra_icons = $('.navbar-extra-icons', $menuCont);
		}

		if ( $extra_icons.length ) {
			if ( $vMenuCont.length ) {
				if ( $('li:not(.social-icon)', $extra_icons).length ) {
					if (UNCODE.wwidth < UNCODE.mediaQuery) {
						var $not_social = $('> ul > li:not(.social-icon)', $extra_icons),
							$primary_after = $('.menu-accordion-primary-after', $vMenuCont);
						$not_social.each(function(){
							if ( ! $primary_after.length ) {
								$('.menu-accordion-primary', $vMenuCont).after('<div class="menu-accordion menu-accordion-primary-after" />');
								$primary_after = $('.menu-accordion-primary-after', $vMenuCont);
								$primary_after.append('<ul class="menu-smart sm sm-vertical menu-smart-social" />');
							}
							var $extra_li = $(this);
							$primary_after.find('> ul').append($extra_li);
						});
					} else {
						var $primary_after = $('.menu-accordion-primary-after', $vMenuCont),
							$not_social = $('> ul > li:not(.social-icon)', $primary_after);
						$not_social.each(function(){
							var $extra_li = $(this);
							$extra_icons.find('> ul').append($extra_li);
						});
					}
				} /*else {
					var $extra_icons_ph = $vMenuCont.add($menuCont).find('.extra-icons-ph');
					if (UNCODE.wwidth < UNCODE.mediaQuery) {
						if ( !$extra_icons_ph.length ) {
							$extra_icons.after('<span class="extra-icons-ph" />');
						}
						if ( $('.navbar-accordion-cta', $vMenuCont).length ) {
							$('.navbar-accordion-cta', $vMenuCont).after($extra_icons);
						} else {
							$('.menu-accordion-primary', $vMenuCont).after($extra_icons);
						}
					} else {
						if ( typeof $extra_icons_ph !== 'undefined' && $extra_icons_ph.length ) {
							$extra_icons_ph.before($extra_icons);
						}
					}
				}*/
			} else {
				if ( ! $body.hasClass('hmenu-center-double') ) {
					if (UNCODE.wwidth < UNCODE.mediaQuery) {
						var $not_social = $('> ul > li:not(.social-icon)', $extra_icons),
							$primary_after = $('.nav.navbar-main-after', $menuCont);

						if ( ! $primary_after.length && $not_social.length ) {
							if ( $('.navbar-nav.navbar-cta:not(.mobile-hidden)', $menuCont).length ) {
								$('.navbar-nav.navbar-cta', $menuCont).after('<div class="nav navbar-main-after" />');
							} else {
								$('.navbar-nav.navbar-main', $menuCont).after('<div class="nav navbar-main-after" />');
							}
							$primary_after = $('.nav.navbar-main-after', $menuCont);
							$primary_after.append('<ul class="menu-smart sm menu-smart-social" role="menu" />');
						}
						var tablet_hidden = true,
							mobile_hidden = true;
						$not_social.each(function(){
							var $extra_li = $(this);
							$primary_after.find('> ul').append($extra_li);
							if ( ! $extra_li.hasClass('tablet-hidden') ) {
								tablet_hidden = false;
							}
							if ( ! $extra_li.hasClass('mobile-hidden') ) {
								mobile_hidden = false;
							}
						});
						if ( tablet_hidden === true && $not_social.length ) {
							$primary_after.addClass('tablet-hidden');
						}
						if ( mobile_hidden === true && $not_social.length ) {
							$primary_after.addClass('mobile-hidden');
						}
					} else {
						var $primary_after = $('.nav.navbar-main-after', $menuCont);

						if ( $primary_after.length ) {
							var $not_social = $('> ul > li:not(.social-icon)', $primary_after);
							$not_social.each(function(){
								var $extra_li = $(this);
								$extra_icons.find('> ul').append($extra_li);
							});
							$primary_after.remove();
						}
					}
				}
			}
		}

		if ( ( $body.hasClass('hmenu-center-double') ) ) {
			appendSplit = function(){
				if (UNCODE.wwidth < UNCODE.mediaQuery) {
					if ( $extra_icons.length ) {
						if ( $('li:not(.social-icon):not(.tablet-hidden):not(.mobile-hidden)', $extra_icons).length ) {
							var $not_social = $('> ul > li:not(.social-icon)', $extra_icons),
								$append_ul = $('<ul class="menu-smart sm sm-vertical append-extra-icons" />');
							$not_social.each(function(){
								var $extra_li = $(this);
								$append_ul.append($extra_li);
							});
							if ( $secondNav.length ) {
								$secondNav.append($append_ul);
							} else {
								$('.menu-horizontal-inner', $menuCont).prepend($append_ul);
							}
						}
					}
					if ( $secondNav.length ) {
						$secondNav.prepend($ulFirst);
					} else {
						$('.menu-horizontal-inner', $menuCont).prepend($ulFirst);
					}
					$firstMenu.hide();
				} else {
					$firstNav.append($ulFirst);
					var $append_ul = $('.menu-horizontal-inner ul.append-extra-icons', $menuCont).eq(0);
					if ( $append_ul.length ) {
						var $not_social = $('> li:not(.social-icon)', $append_ul);
						$not_social.each(function(){
							var $extra_li = $(this);
							$extra_icons.find('> ul').append($extra_li);
						});
					}
					$('.menu-horizontal-inner ul.append-extra-icons', $menuCont).remove();
					$('.menu-horizontal-inner > .menu-primary-inner', $menuCont).remove();
					$firstMenu.css({
						'display': 'table-cell'
					});
				}
			}
		}
		appendSplit();

		$(window).on( 'wwresize', function(){
			clearRequestTimeout(setCTA);
			setCTA = requestTimeout( function() {
				appendCTA();
				appendSocials();
				appendSplit();
			}, 10 );
		});
	}
	//menuMobileButton();
	menuMobile();
	menuOffCanvas();
	menuAppend();
	menuOverlay();

	var stickyDropdownSearch = function(){
		var $masthead = $('#masthead'),
			$ddSearch = $('.overlay.overlay-search', $masthead),
			$styles = $('#stickyDropdownSearch').remove();
		if ( $('body.hmenu-center.menu-sticky-mobile').length && $ddSearch.length ) {
			var $menuWrapper = $('.menu-wrapper'),
				$navbar = $('.menu-container-mobile', $menuWrapper),
				navbarH = $navbar.outerHeight(),
				//$topbar = $('.top-menu', $menuWrapper),
				//topbarH = $topbar.outerHeight(),
				_css;

			_css = '<style id="stickyDropdownSearch">';
			_css += '@media (max-width: 959px) {';
			_css += 'body.hmenu-center.menu-sticky-mobile #masthead .overlay.overlay-search {';
			_css += 'margin-top: ' + parseFloat(navbarH) + 'px !important;';
			_css += '}';
			_css += 'body.hmenu-center.menu-sticky-mobile .navbar.is_stuck + #masthead .overlay.overlay-search {';
			_css += 'position: fixed;';
			_css += 'top: 0;';
			_css += '}';
			_css += '</style>';

			$(_css).appendTo($('head'));
		}
	}
	stickyDropdownSearch();

	var setMenuOverlay;
	$(window).on( 'wwResize', function(){
		if ( $('.overlay').length && $(window).width() > 1024 ) {
			$('.overlay').addClass('hidden');
		}
		clearRequestTimeout(setMenuOverlay);
		setMenuOverlay = requestTimeout( function(){
			menuOverlay();
			menuAppend();
			stickyDropdownSearch();
		}, 150 );
	});
	UNCODE.menuMegaBlock();
	UNCODE.menuSmartInit();
	$(window).on('wwresize', function(){
		UNCODE.menuSmartInit();
	});
};

UNCODE.menuSmartInit = function() {
	var $menusmart = $('[class*="menu-smart"]'),
		$masthead = $('#masthead'),
		$hMenu = $('.menu-horizontal-inner', $masthead),
		$focus = $('.overlay-menu-focus'),
		$uls_anim = $('> li> ul[role="menu"]', $menusmart),
		showTimeout = 50,
		hideTimeout = 50,
		showTimeoutFunc, hideTimeoutFunc, subMenuRT;

	if ( typeof $masthead.attr('data-menu-anim') !== 'undefined' && $masthead.attr('data-menu-anim') !== '' ) {
		var menu_anim = $masthead.attr('data-menu-anim');
		$uls_anim.each(function(){
			var $ul_anim = $(this);
			if ( !$('> li[data-block]', $ul_anim).length ) {
				$ul_anim.addClass('animate_when_almost_visible');
				$ul_anim.addClass(menu_anim);
			} else {
				var dataB = $('> li[data-block]', $ul_anim).attr('data-block');
				if ( dataB !== 'no-anim' && !dataB.includes('slight-anim') ) {
					$ul_anim.addClass('animate_when_almost_visible');
					$ul_anim.addClass(dataB);
				}
			}
		});
	}

	$('> li.menu-item-has-children', $menusmart).hover(function(){
		$(this).data('hover', true);
	}, function(){
		$(this).data('hover', false);
	});

	$('> li.menu-item-has-children', $menusmart).each(function(){
		var $a = $('> a', this).attr('aria-haspopup', 'true').attr('aria-expanded', 'false')
	});

	$('> li.menu-item a[href="#"]', $menusmart).on('click', function(e){
		e.preventDefault();
	});

	if ( $(window).width() >= UNCODE.mediaQuery && $('.overlay-menu-focus').length ) {
			
		var $notLis = $('> .nav > ul > li a', $hMenu),
			$menuA = $('a', $masthead).not($notLis),
			$hoverSelector = $('> .nav > ul > li', $hMenu).has('> ul'),
			showFuncCond = function() { return true };

		if ( $('body').hasClass('focus-megamenu') ) {
			$hoverSelector = $('> .nav > ul > li', $hMenu).has('.need-focus');
			showFuncCond = function($ul) { return $ul.hasClass('need-focus') };
		} else if ( $('body').hasClass('focus-links') ) {
			$hoverSelector = $('> .nav > ul > li', $hMenu).add($menuA);
		}

		$hoverSelector.hover(function(){
			clearRequestTimeout(hideTimeoutFunc);
			showTimeoutFunc = requestTimeout(function(){
				$('body').addClass('navbar-hover');
			}, showTimeout*2);
		}, function(){
			hideTimeoutFunc = requestTimeout(function(){
				if ( ! $('.overlay-search.open', $masthead).length ) {
					$('body').removeClass('navbar-hover');
				}
			}, hideTimeout*2);
		});
	} else {
		showFuncCond = function() { return false };
	}

	if ($menusmart.length > 0) {
		var objShowTimeout;
		$menusmart.smartmenus({
			subIndicators: false,
			subIndicatorsPos: 'append',
			//subMenusMinWidth: '13em',
			showOnClick: SiteParameters.menuShowOnClick,
			subIndicatorsText: '',
			showTimeout: showTimeout,
			hideTimeout: hideTimeout,
			scrollStep: 8,
			showFunction: function($ul, complete) {
				$(document).trigger('un-menu-show', $ul);
				clearRequestTimeout(showTimeoutFunc);
				$ul.fadeIn(0, 'linear', function(){
					complete();
					if ( $ul.hasClass('vc_row') ) {
						$ul.css({
							'display': 'table'
						});
					}
					if ( $('.overlay-menu-focus').length && $ul.hasClass('need-focus') ) {
						$('body').addClass('open-megamenu');
					}
					if ( $('.overlay-menu-focus').length && showFuncCond($ul) && $(window).width() >= UNCODE.mediaQuery && $ul.closest('.main-menu-container').length ) {
						$('body').addClass('navbar-hover');
					}
					var showed = 0;
					$('.animate_when_almost_visible', $ul).each(function(index, val){
						var $element = $(this),
							delayAttr = $element.attr('data-delay');
						if ( !$element.closest('.owl-carousel').length && ! $element.closest('.cssgrid-animate-sequential').length ) {
							if (delayAttr == undefined) delayAttr = 0;
							requestTimeout(function() {
								$element.addClass('start_animation');
							}, delayAttr);
						} else if ( $element.closest('.cssgrid-animate-sequential').length ) {
							var grid = $element.closest('.cssgrid-animate-sequential');

							var delay = index,
								delayAttr = parseInt($element.attr('data-delay'));
							if (isNaN(delayAttr)) delayAttr = 100;
							delay -= showed;
							objShowTimeout = requestTimeout(function() {
								$element.removeClass('zoom-reverse').addClass('start_animation');
								showed = index;
							}, delay * delayAttr);
						}
					});
					if ( $ul.is('.animate_when_almost_visible') ) {
						$ul.addClass('start_animation');
					}
				}).addClass('open-animated');
				menuStartBuilderAnimations($ul);
			},
			hideFunction: function($ul, complete) {
				if ( $('.overlay-menu-focus').length && $ul.hasClass('need-focus') && ! $('.overlay-search.open', $masthead).length ) {
					$('body').removeClass('open-megamenu');
				}
				var fixIE = $('html.ie').length;
				if (fixIE) {
					var $rowParent = $($ul).closest('.main-menu-container');
					$rowParent.height('auto');
				}
				$ul.fadeOut(0, 'linear', function(){
					complete();
					$ul.removeClass('open-animated');
					if ( $ul.closest('li.menu-item-has-children').data('hover') === false ) {
						$('body').removeClass('open-submenu');
					}
					$('.animate_when_almost_visible', $ul).each(function(){
						$(this).removeClass('start_animation');
					});
					if ( $ul.is('.animate_when_almost_visible') ) {
						$ul.removeClass('start_animation');
					}
				});
				$ul.find('.start_animation').each(function(index, val) {
					clearRequestTimeout(subMenuRT);
					$(val).removeClass('start_animation');
				});
				$(document).trigger('un-menu-hide', $ul);
				clearRequestTimeout(objShowTimeout);
			},
			collapsibleShowFunction: function($ul, complete) {
				$ul.slideDown(400, 'easeInOutCirc', function(){
					UNCODE.animateSubInside($ul);
					complete();
				});
			},
			collapsibleHideFunction: function($ul, complete) {
				$ul.slideUp(200, 'easeInOutCirc', function(){
					complete();
				});
				$(document).trigger('sub-menu-slide-up');
			},
			hideOnClick: SiteParameters.menuHideOnClick,
		});

		if ( $('body').hasClass('menu-accordion-active') ) {
			$menusmart.each(function(key, menu){
				$(menu).addClass('menu-smart-init');
				$(menu).smartmenus( 'itemActivate', $(menu).find( '.current-menu-item > a' ).eq( -1 ) );
			});
		}

		$menusmart.each(function(key, menu){
			$(menu).on('beforecollapse.smapi', function( e, $sub ){
				if ( $('> .trigger-window-resize', $sub).length ) {
					window.dispatchEvent(new Event('resize'));
					$(window).trigger('uncode.re-layout');
				} else if ( $('> .trigger-box-resize', $sub).length ) {
					window.dispatchEvent(new CustomEvent('boxResized'));
				}
				return false;
			});
		});
		
		$(document).on( 'uncode.smartmenu-appended', function(){
			requestTimeout(function(){
				$menusmart.smartmenus( 'refresh' );
			}, 1000);
		});

		function menuStartBuilderAnimations($ul){
			/*$ul.find('.animate_when_almost_visible:not(.t-inside):not(.drop-image-separator), .tmb-linear .animate_when_almost_visible, .index-scroll .animate_when_almost_visible, .tmb-media .animate_when_almost_visible, .animate_when_almost_visible.has-rotating-text, .custom-grid-container .animate_when_almost_visible').each(function(index, val) {
					var $elAnim = $(val);
					if ( $elAnim.hasClass('el-text-split') || ( ( $elAnim.closest('.unscroll-horizontal').length || $elAnim.closest('.index-scroll').length || $elAnim.closest('.tab-pane:not(.active)').length || $elAnim.closest('.panel:not(.active-group)').length ) && !SiteParameters.is_frontend_editor ) ) {
						return true;
					}
					var run = true,
						$carousel = $elAnim.closest('.owl-carousel'),
						marquee = $elAnim.closest('.tmb-linear').length;
					if ( $carousel.length ) {
						run = false;
					}
					if (run) {
						var delayAttr = $elAnim.attr('data-delay');
						if (delayAttr == undefined) delayAttr = 0;
						subMenuRT = requestTimeout(function() {
							$elAnim.addClass('start_animation');
						}, delayAttr);
					}
			});*/
		};
	}

	$('.main-menu-container').each(function(){
		var $main_cont = $(this),
			$uls = $('ul:not(.nav-tabs)', $main_cont);

		$uls.each(function(){
			var $ul = $(this),
				mobile_hidden = true,
				tablet_hidden = true;
			$('> li:not(.hidden)', $ul).each(function(){
				if ( !$(this).hasClass('mobile-hidden') ) {
					mobile_hidden = false;
					return false;
				}
			});
			$('> li:not(.hidden)', $ul).each(function(){
				if ( !$(this).hasClass('tablet-hidden') ) {
					tablet_hidden = false;
					return false;
				}
			});
			if ( mobile_hidden ) {
				$ul.addClass('mobile-hidden');
			}
			if ( tablet_hidden ) {
				$ul.addClass('tablet-hidden');
			}
		});

		var $divUlsMB = $('div:has(>ul.mobile-hidden)');

		$divUlsMB.each(function(){
			var $divUlMB = $(this),
				div_mobile_hidden = true,
				div_tablet_hidden = true;

			$('> ul:not(.hidden)', $divUlMB).each(function(){
				if ( !$(this).hasClass('mobile-hidden') ) {
					div_mobile_hidden = false;
					return false;
				}
			});
			$('> ul:not(.hidden)', $divUlMB).each(function(){
				if ( !$(this).hasClass('tablet-hidden') ) {
					div_tablet_hidden = false;
					return false;
				}
			});
			if ( div_mobile_hidden ) {
				$divUlMB.addClass('mobile-hidden');
			}
			if ( div_tablet_hidden ) {
				$divUlMB.addClass('tablet-hidden');
			}
		});
	});

	var overlaySearchButton = function(){
		var $search_wrap = $('.overlay.overlay-search, .widget_search');

		$search_wrap.each(function(){
			var $form = $('form', this),
				$icon = $('i', $form);

			$icon.on('click', function(){
				$form.submit();
			});
		});
	};
	overlaySearchButton();
}

UNCODE.menuMegaBlock = function() {
	var $megaBlocks = $('.megamenu-block-wrapper');
	if ( ! $megaBlocks.length ) {
		return;
	}

	$megaBlocks.each(function(){
		var $megaLi = $(this),
			//$parentLi = $megaLi.closest('li.menu-item').addClass('mega-menu'),
			dataLi = $megaLi.attr('data-block'),
			$megaUl = $megaLi.closest('ul').addClass('block-wrapper-parent unmenu-inner-ul'),
			$innerUl = $megaLi.find('ul').addClass('unmenu-inner-ul');
		$megaUl.addClass(dataLi);
		//$('.column_parent > .uncol .uncont:not(.uncont *)', $megaLi).addClass(dataLi);
		$('img', $megaLi).removeAttr('loading');
	});
}

UNCODE.unBlockMenu = function(){
	//Menu block
	var $unBlockMenus = $('.menu.unmenu-block');

	$unBlockMenus.each(function(){
		var $unBlockMenu = $(this),
			$lastOnes;

		if ( !$unBlockMenu.hasClass('first-grid') ) {
			$lastOnes = $('ul', $unBlockMenu).last();
			$lastOnes.each(function(){
                var _cols = $(this).css('grid-template-columns');
				if ( _cols.split(' ').length > 1 && !_cols.startsWith('repeat(1,')) {
					$unBlockMenu.addClass('has-last-ones');
					$(this).addClass('last-one');
				} else {
					var $parentLi = $(this).closest('li'),
						$siblingLi = $parentLi.next();
					if ( !$siblingLi.length ) {
						$unBlockMenu.addClass('has-last-ones');
						$(this).addClass('last-one');
					}
				}
			});
		} else {
			var checkGrids = function(){
				$unBlockMenu.find('.last-ul').removeClass('last-one');
				var _cols = $unBlockMenu.css('grid-template-columns'),
                    cols = _cols.match(/^repeat\(\s*(\d+)\s*,/) ? parseInt(_cols.match[1], 10) : _cols.split(' ').length,
					$lis = $('> li', $unBlockMenu),
					totLi = $lis.length,
					lastRowCount = totLi - (totLi % cols || cols),
					$lastLis = $lis.slice(cols).addClass('last-lis'),
    				$lastRowLis = $lis.slice(lastRowCount);
				$lastRowLis.each(function(){
					var $lastRowLi = $(this);
					$lastRowLi.find('ul').last().addClass('last-one');
				});
			}
			$(window).on('wwResize', checkGrids);
			checkGrids();

		}

	});

	//Accordion
	var $tgglBlocks = $('.unmenu-collapse');
	$tgglBlocks.each(function(key, val){
		var $acc = $(val),
			$titles = $('.menu-item-has-children > *:first-child', $acc),
			run = true;

		var checkRun = function(){
			if ( $acc.hasClass('unmenu-collapse-mobile') && UNCODE.wwidth >= UNCODE.mediaQuery ) {
				run = false;
			} else {
				run = true;
			}

			if ( $titles.length ) {
				$titles.each(function(_key, _val){
					var $title = $(_val),
						$sub = $title.closest('li').find('> ul.un-submenu');

					$sub.each(function(key, val){
						if ( run ) {
							$(val).slideUp({
								duration: 1,
								complete: function() {
									$(this).addClass('toggle-init');
								}
							});
						} else {
							$(val).show();
						}
					});
				});
			}
		}
		checkRun();

		$(window).on('wwResize', checkRun);

		if ( $titles.length ) {
			$titles.each(function(_key, _val){
				var $title = $(_val),
					$sub = $title.closest('li').find('> ul.un-submenu'),
					$menu = $sub.closest('.menu.unmenu-block'),
					$ulS;

				$title.off('click').on('click', function(e){
					e.preventDefault();
					$ulS = $menu.find('ul:visible').not($sub).not($sub.parents('ul'))
					if ( run ) {
						if ( $sub.is(':visible') ) {
							$sub.slideUp(200, 'easeInOutCirc', function(){
								$.each($('.start_animation', $sub), function(index, val) {
									var element = $(val);
									element.removeClass('start_animation');

								});
								$sub.parents('li').eq(0).addClass('un-submenu-closed').removeClass('un-submenu-open');
								$(document).trigger('sub-menu-slide-up');
							});
						} else {
							$ulS.slideUp({
								duration: 200,
								easing: 'easeInOutCirc',
								start: function(){
									var _this = $(this);
									_this.parents('li').eq(0).addClass('un-submenu-closed').removeClass('un-submenu-open');
									$(document).trigger('sub-menu-slide-up');
								}
							});

							$sub.slideDown({
								duration: 400,
								easing: 'easeInOutCirc',
								start: function() {
									var _this = $(this);
									_this.css('height', 0);
									if ( _this.hasClass('is-grid') ) {
										_this.css('display','grid');
									}

									UNCODE.animateSubInside(_this)
									_this.parents('li').eq(0).addClass('un-submenu-open').removeClass('un-submenu-closed');
								}
							});
						}

					}
				});
			});

			if ( $acc.closest('#masthead').length ) {
				$(window).on('menuMobileClose menuCanvasClose unmodal-close uncode-sidecart-closed', function(){
					$acc.find('ul.un-submenu').slideUp(200, 'easeInOutCirc');
				});
			}
		}

	});
};

UNCODE.mobileMenuBlockSkins = function(){
	var $mobileBlock = $('#uncode-mobile-menu-block'),
		$parentDark = $mobileBlock.closest('.submenu-dark'),
		$parentLight = $mobileBlock.closest('.submenu-light');


	if ( $parentDark.length ) {
		var $cols = $('.uncol.style-light:not(.style-spec):not(.style-run)', $mobileBlock);
		$cols.each(function(key, val){
			var $col = $(val).removeClass('style-light').addClass('style-dark style-run');
		});
	}

	if ( $parentLight.length ) {
		var $cols = $('.uncol.style-dark:not(.style-spec):not(.style-run)', $mobileBlock);
		$cols.each(function(key, val){
			var $col = $(val).removeClass('style-dark').addClass('style-light style-run');
		});
	}
};

})(jQuery);
