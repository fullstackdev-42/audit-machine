function adjust_dropui_positions(F) {
    var w = parseInt($("#form_container").css("border-top-width"));
    var s = $("#form_header_preview").outerHeight();
    var f = 20;
    var k = $("#form_title_preview").outerHeight();
    var G = $("#form_desc_preview").outerHeight();
    var x = $("#form_theme_preview div.form_description").outerHeight(true);
    var i = $("#li_1 label.description").height();
    var A = $("#li_1").outerHeight(true);
    var g = $("#li_2").outerHeight(true);
    var z = $("#li_3").outerHeight(true);
    var C = $("#li_4").outerHeight(true);
    var v = $("#guide_2").outerHeight(true);
    var D = parseInt($("#li_4").css("padding-top"));
    var o = parseInt($("#li_4").css("border-top-width"));
    var a = $("#section_title_preview").height();
    var p = $("#section_desc_preview").height();
    if (F == "logo") {
        var t = w + s - 23;
        $("#dropui-form-logo").css("top", t + "px")
    } else {
        if (F == "backgrounds") {
            var y = w + s + f + k + G - 18;
            $("#dropui-bg-form").css("top", y + "px");
            var c = w + s + f + x + A + g + 58;
            $("#dropui-bg-highlight").css("top", c + "px");
            var u = w + s + f + x + A + v - 27;
            $("#dropui-bg-guidelines").css("top", u + "px");
            var E = w + s + f + x + A + g + z + C - 34;
            $("#dropui-bg-field").css("top", E + "px")
        } else {
            if (F == "fonts") {
                var n = w + s + f + k - 26;
                $("#dropui-typo-form-title").css("top", n + "px");
                var m = w + s + f + k + G + 2;
                $("#dropui-typo-form-desc").css("top", m + "px");
                var l = w + s + f + x + i - 19;
                $("#dropui-typo-field-title").css("top", l + "px");
                var h = w + s + f + x + A + v - 27;
                $("#dropui-typo-guidelines").css("top", h + "px");
                var b = w + s + f + x + A + g + o + a - 2;
                $("#dropui-typo-section-title").css("top", b + "px");
                var r = w + s + f + x + A + g + o + a + p + 76;
                $("#dropui-typo-section-desc").css("top", r + "px");
                var B = w + s + f + x + A + g + z + C - 34;
                $("#dropui-typo-field-text").css("top", B + "px")
            } else {
                if (F == "borders") {
                    var e = w + s + f + x + A + v - 27;
                    $("#dropui-border-guidelines").css("top", e + "px");
                    var q = w + s + f + x + A + g + 68;
                    $("#dropui-border-section").css("top", q + "px")
                } else {
                    if (F == "shadows") {
                        var d = $("#form_container").outerHeight() + 24;
                        $("#dropui-form-shadow").css("top", d + "px")
                    } else {
                        if (F == "buttons") {
                            var j = $("#form_container").outerHeight() - 51;
                            $("#dropui-form-button").css("top", j + "px")
                        }
                    }
                }
            }
        }
    }
}

function rgb2hex(a) {
    if (a.charAt(0) == "#") {
        return a
    } else {
        a = a.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
        return "#" + ("0" + parseInt(a[1], 10).toString(16)).slice(-2) + ("0" + parseInt(a[2], 10).toString(16)).slice(-2) + ("0" + parseInt(a[3], 10).toString(16)).slice(-2)
    }
}

function ucfirst(a) {
    return a.charAt(0).toUpperCase() + a.slice(1)
}

function is_support_html5_uploader() {
    if (window.File && window.FileList && window.Blob && (window.FileReader || window.FormData)) {
        return true
    } else {
        return false
    }
}

function reload_theme() {
    var a = $("#et_theme_preview").data("theme_properties");
    if (a.wallpaper_bg_type == "color") {
        $("#et_theme_preview").css("background-image", "");
        $("#et_theme_preview").css("background-color", a.wallpaper_bg_color)
    } else {
        if (a.wallpaper_bg_type == "pattern") {
            $("#et_theme_preview").css("background-image", "url('images/form_resources/" + a.wallpaper_bg_pattern + "')");
            $("#et_theme_preview").css("background-repeat", "repeat")
        } else {
            if (a.wallpaper_bg_type == "custom") {
                $("#et_theme_preview").css("background-color", "#ececec");
                $("#et_theme_preview").css("background-image", "url('" + a.wallpaper_bg_custom + "')");
                $("#et_theme_preview").css("background-repeat", "repeat")
            }
        }
    }
    if (a.header_bg_type == "color") {
        $("#form_header_preview").css("background-image", "");
        $("#form_header_preview").css("background-color", a.header_bg_color)
    } else {
        if (a.header_bg_type == "pattern") {
            $("#form_header_preview").css("background-image", "url('images/form_resources/" + a.header_bg_pattern + "')");
            $("#form_header_preview").css("background-repeat", "repeat")
        } else {
            if (a.header_bg_type == "custom") {
                $("#form_header_preview").css("background-color", "#ececec");
                $("#form_header_preview").css("background-image", "url('" + a.header_bg_custom + "')");
                $("#form_header_preview").css("background-repeat", "repeat")
            }
        }
    }
    if (a.form_bg_type == "color") {
        $("#form_container").css("background-image", "");
        $("#form_container").css("background-color", a.form_bg_color)
    } else {
        if (a.form_bg_type == "pattern") {
            $("#form_container").css("background-image", "url('images/form_resources/" + a.form_bg_pattern + "')");
            $("#form_container").css("background-repeat", "repeat")
        } else {
            if (a.form_bg_type == "custom") {
                $("#form_container").css("background-color", "#ececec");
                $("#form_container").css("background-image", "url('" + a.form_bg_custom + "')");
                $("#form_container").css("background-repeat", "repeat")
            }
        }
    }
    if (a.highlight_bg_type == "color") {
        $("#li_fields li.highlighted").css("background-image", "");
        $("#li_fields li.highlighted").css("background-color", a.highlight_bg_color)
    } else {
        if (a.highlight_bg_type == "pattern") {
            $("#li_fields li.highlighted").css("background-image", "url('images/form_resources/" + a.highlight_bg_pattern + "')");
            $("#li_fields li.highlighted").css("background-repeat", "repeat")
        } else {
            if (a.highlight_bg_type == "custom") {
                $("#li_fields li.highlighted").css("background-color", "#ececec");
                $("#li_fields li.highlighted").css("background-image", "url('" + a.highlight_bg_custom + "')");
                $("#li_fields li.highlighted").css("background-repeat", "repeat")
            }
        }
    }
    if (a.guidelines_bg_type == "color") {
        $("#li_fields p.guidelines").css("background-image", "");
        $("#li_fields p.guidelines").css("background-color", a.guidelines_bg_color)
    } else {
        if (a.guidelines_bg_type == "pattern") {
            $("#li_fields p.guidelines").css("background-image", "url('images/form_resources/" + a.guidelines_bg_pattern + "')");
            $("#li_fields p.guidelines").css("background-repeat", "repeat")
        } else {
            if (a.guidelines_bg_type == "custom") {
                $("#li_fields p.guidelines").css("background-color", "#ececec");
                $("#li_fields p.guidelines").css("background-image", "url('" + a.guidelines_bg_custom + "')");
                $("#li_fields p.guidelines").css("background-repeat", "repeat")
            }
        }
    }
    if (a.field_bg_type == "color") {
        $("#form_theme_preview :input").not(".submit_button").css("background-image", "");
        $("#form_theme_preview :input").not(".submit_button").css("background-color", a.field_bg_color)
    } else {
        if (a.field_bg_type == "pattern") {
            $("#form_theme_preview :input").not(".submit_button").css("background-image", "url('images/form_resources/" + a.field_bg_pattern + "')");
            $("#form_theme_preview :input").not(".submit_button").css("background-repeat", "repeat")
        } else {
            if (a.field_bg_type == "custom") {
                $("#form_theme_preview :input").not(".submit_button").css("background-color", "#ececec");
                $("#form_theme_preview :input").not(".submit_button").css("background-image", "url('" + a.field_bg_custom + "')");
                $("#form_theme_preview :input").not(".submit_button").css("background-repeat", "repeat")
            }
        }
    }
    $("#form_title_preview").css("font-family", "'" + a.form_title_font_type + "','Lucida Grande',Tahoma,Arial,sans-serif");
    $("#form_title_preview").css("font-weight", a.form_title_font_weight);
    $("#form_title_preview").css("font-style", a.form_title_font_style);
    $("#form_title_preview").css("font-size", a.form_title_font_size);
    $("#form_title_preview").css("color", a.form_title_font_color);
    $("#form_desc_preview").css("font-family", "'" + a.form_desc_font_type + "','Lucida Grande',Tahoma,Arial,sans-serif");
    $("#form_desc_preview").css("font-weight", a.form_desc_font_weight);
    $("#form_desc_preview").css("font-style", a.form_desc_font_style);
    $("#form_desc_preview").css("font-size", a.form_desc_font_size);
    $("#form_desc_preview").css("color", a.form_desc_font_color);
    $("#form_theme_preview label").css("font-family", "'" + a.field_title_font_type + "','Lucida Grande',Tahoma,Arial,sans-serif");
    $("#form_theme_preview label.description").css("font-weight", a.field_title_font_weight);
    $("#form_theme_preview label.description").css("font-style", a.field_title_font_style);
    $("#form_theme_preview label.description").css("font-size", a.field_title_font_size);
    $("#form_theme_preview label").css("color", a.field_title_font_color);
    $("#li_fields p.guidelines small").css("font-family", "'" + a.guidelines_font_type + "','Lucida Grande',Tahoma,Arial,sans-serif");
    $("#li_fields p.guidelines small").css("font-weight", a.guidelines_font_weight);
    $("#li_fields p.guidelines small").css("font-style", a.guidelines_font_style);
    $("#li_fields p.guidelines small").css("font-size", a.guidelines_font_size);
    $("#li_fields p.guidelines small").css("color", a.guidelines_font_color);
    $("#section_title_preview").css("font-family", "'" + a.section_title_font_type + "','Lucida Grande',Tahoma,Arial,sans-serif");
    $("#section_title_preview").css("font-weight", a.section_title_font_weight);
    $("#section_title_preview").css("font-style", a.section_title_font_style);
    $("#section_title_preview").css("font-size", a.section_title_font_size);
    $("#section_title_preview").css("color", a.section_title_font_color);
    $("#section_desc_preview").css("font-family", "'" + a.section_desc_font_type + "','Lucida Grande',Tahoma,Arial,sans-serif");
    $("#section_desc_preview").css("font-weight", a.section_desc_font_weight);
    $("#section_desc_preview").css("font-style", a.section_desc_font_style);
    $("#section_desc_preview").css("font-size", a.section_desc_font_size);
    $("#section_desc_preview").css("color", a.section_desc_font_color);
    $("#form_theme_preview :input").not(".submit_button").css("font-family", "'" + a.field_text_font_type + "','Lucida Grande',Tahoma,Arial,sans-serif");
    $("#form_theme_preview :input").not(".submit_button").css("font-weight", a.field_text_font_weight);
    $("#form_theme_preview :input").not(".submit_button").css("font-style", a.field_text_font_style);
    $("#form_theme_preview :input").not(".submit_button").css("font-size", a.field_text_font_size);
    $("#form_theme_preview :input").not(".submit_button").css("color", a.field_text_font_color);
    $("#form_container").css("border-width", a.border_form_width + "px");
    $("#form_container").css("border-style", a.border_form_style);
    $("#form_container").css("border-color", a.border_form_color);
    $("#guide_2").css("border-width", a.border_guidelines_width + "px");
    $("#guide_2").css("border-style", a.border_guidelines_style);
    $("#guide_2").css("border-color", a.border_guidelines_color);
    $("#li_4").css("border-top-width", a.border_section_width + "px");
    $("#li_4").css("border-top-style", a.border_section_style);
    $("#li_4").css("border-top-color", a.border_section_color)
}
$(function() {
    var a = $("#et_theme_preview").data("theme_properties");
    $("#et_theme_button_ul li").click(function() {
        var b = $(this).attr("id");
        var d;
        var c = $("#et_theme_button_ul > li.current");
        $("#main_body div.dropui-circle").hide();
        c.removeClass("current");
        if (c.attr("id") != b) {
            $(this).addClass("current");
            if (b == "li_tab_logo") {
                d = "et-prop-logo";
                adjust_dropui_positions("logo")
            } else {
                if (b == "li_tab_backgrounds") {
                    d = "et-prop-bg";
                    adjust_dropui_positions("backgrounds")
                } else {
                    if (b == "li_tab_fonts") {
                        d = "et-prop-typo";
                        adjust_dropui_positions("fonts")
                    } else {
                        if (b == "li_tab_borders") {
                            d = "et-prop-border";
                            adjust_dropui_positions("borders")
                        } else {
                            if (b == "li_tab_shadows") {
                                d = "et-prop-shadow";
                                adjust_dropui_positions("shadows")
                            } else {
                                if (b == "li_tab_buttons") {
                                    d = "et-prop-button";
                                    adjust_dropui_positions("buttons")
                                }
                            }
                        }
                    }
                }
            }
            $("#main_body div." + d).fadeIn()
        }
    });
    $("#et_theme_preview a.dropui-close").click(function() {
        $(this).parents("div.hovered").removeClass("hovered");
        $(this).parents("div.dropui-content").hide();
        return false
    });
    $("#et_ul_form_logo input[type=radio]").click(function() {
        $("#" + $("#et_ul_form_logo li.prop_selected input").attr("id") + "_tab").hide();
        $("#et_ul_form_logo li.prop_selected").removeClass("prop_selected");
        $(this).parent().addClass("prop_selected");
        $("#" + $(this).attr("id") + "_tab").fadeIn();
        var b = $(this).attr("id");
        $("#et_form_logo_content").css("height", "");
        if (b == "et_form_logo_none") {
            $("#form_logo_preview").animate({
                height: "40px"
            }, {
                duration: 200,
                queue: false,
                complete: function() {
                    adjust_dropui_positions("logo")
                }
            });
            a.logo_type = "disabled";
            $("#form_logo_preview").css("background-image", "url('images/form_resources/nologo.png')")
        } else {
            if (b == "et_form_logo_default") {
                $("#form_logo_preview").animate({
                    height: "40px"
                }, {
                    duration: 200,
                    queue: false,
                    complete: function() {
                        adjust_dropui_positions("logo")
                    }
                });
                a.logo_type = "default";
                var c = a.logo_default_image;
                $("#form_logo_preview").css("background-image", "url('images/form_resources/" + c + "')");
                $("#form_logo_preview").css("background-repeat", "no-repeat")
            } else {
                if (b == "et_form_logo_custom") {
                    a.logo_type = "custom";
                    $("#et_form_logo_content").css("height", "100%");
                    $("#form_logo_preview").css("background-image", "url('" + a.logo_custom_image + "')");
                    $("#form_logo_preview").css("background-repeat", "no-repeat");
                    $("#form_logo_preview").animate({
                        height: a.logo_custom_height + "px"
                    }, {
                        duration: 200,
                        queue: false,
                        complete: function() {
                            adjust_dropui_positions("logo")
                        }
                    })
                }
            }
        }
    });
    $("#et_logo_default_dropdown").bind("change", function() {
        var b = $(this).val();
        $("#form_logo_preview").css("background-image", "url('images/form_resources/" + b + "')");
        $("#form_logo_preview").css("background-repeat", "no-repeat");
        a.logo_default_image = b
    });
    $("#et_your_logo_submit").click(function() {
        $("#form_logo_preview").css("background-image", "url('" + $("#et_your_logo_url").val() + "')");
        var b = $("#et_your_logo_height").val();
        $("#form_logo_preview").animate({
            height: b + "px"
        }, {
            duration: 200,
            queue: false,
            complete: function() {
                adjust_dropui_positions("logo")
            }
        });
        a.logo_type = "custom";
        a.logo_custom_height = parseInt(b);
        a.logo_custom_image = $("#et_your_logo_url").val()
    });
    $("#dropui-form-logo a.dropui-tab").click(function() {
        $(this).parent().addClass("hovered");
        $(this).next().show();
        $("#et_ul_form_logo li.prop_selected").removeClass("prop_selected");
        $("#et_form_logo_content > div").hide();
        $("#et_your_logo_url").val(a.logo_custom_image);
        $("#et_your_logo_height").val(a.logo_custom_height);
        $("#et_logo_default_dropdown").val(a.logo_default_image);
        if (a.logo_type == "disabled") {
            $("#et_form_logo_none").parent().addClass("prop_selected");
            $("#et_form_logo_none").prop("checked", true);
            $("#et_form_logo_none_tab").show()
        } else {
            if (a.logo_type == "default") {
                $("#et_form_logo_default").parent().addClass("prop_selected");
                $("#et_form_logo_default").prop("checked", true);
                $("#et_form_logo_default_tab").show()
            } else {
                if (a.logo_type == "custom") {
                    $("#et_form_logo_custom").parent().addClass("prop_selected");
                    $("#et_form_logo_custom").prop("checked", true);
                    $("#et_form_logo_content").css("height", "100%");
                    $("#et_form_logo_custom_tab").show()
                }
            }
        }
    });
    $("#dropui-bg-main a.dropui-tab").click(function() {
        $("#dropui-bg-main,#dropui-bg-header,#dropui-bg-form,#dropui-bg-highlight,#dropui-bg-guidelines,#dropui-bg-field").removeClass("hovered");
        $("#dropui-bg-main div.dropui-content,#dropui-bg-header div.dropui-content,#dropui-bg-form div.dropui-content,#dropui-bg-highlight div.dropui-content,#dropui-bg-guidelines div.dropui-content,#dropui-bg-field div.dropui-content").hide();
        $(this).parent().addClass("hovered");
        $(this).next().show();
        $("#et_ul_form_wallpaper li.prop_selected").removeClass("prop_selected");
        $("#et_form_wallpaper_content > div").hide();
        $("#et_form_wallpaper_minicolor_box").css("background-image", "");
        $("#et_form_wallpaper_minicolor_box").css("background-color", a.wallpaper_bg_color);
        $("#et_form_wallpaper_pattern_box").css("background-image", "url('images/form_resources/" + a.wallpaper_bg_pattern + "')");
        $("#et_form_wallpaper_pattern_box").css("background-repeat", "repeat");
        var b = $('#et_form_wallpaper_pattern_tab ul.et_pattern_picker > li[data-pattern="' + a.wallpaper_bg_pattern + '"]').index() + 1;
        $("#et_form_wallpaper_pattern_number").text("#" + b);
        $("#et_form_wallpaper_pattern_tab ul li:eq(" + (b - 1) + ")").addClass("picker_selected");
        $("#et_wallpaper_custom_bg").val(a.wallpaper_bg_custom);
        if (a.wallpaper_bg_type == "color") {
            if (a.wallpaper_bg_color == "transparent") {
                $("#et_form_wallpaper_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
                $("#et_form_wallpaper_minicolor_box").css("background-repeat", "repeat");
                $("#et_form_wallpaper_color_tab ul li:eq(0)").addClass("picker_selected")
            } else {
                $("#et_form_wallpaper_minicolor_input").miniColors("value", a.wallpaper_bg_color)
            }
            $("#et_form_wallpaper_color").prop("checked", true);
            $("#et_form_wallpaper_color").parent().addClass("prop_selected");
            $("#et_form_wallpaper_color_tab").show()
        } else {
            if (a.wallpaper_bg_type == "pattern") {
                $("#et_form_wallpaper_pattern").prop("checked", true);
                $("#et_form_wallpaper_pattern").parent().addClass("prop_selected");
                $("#et_form_wallpaper_pattern_tab").show()
            } else {
                if (a.wallpaper_bg_type == "custom") {
                    $("#et_form_wallpaper_custom").prop("checked", true);
                    $("#et_form_wallpaper_custom").parent().addClass("prop_selected");
                    $("#et_form_wallpaper_custom_tab").show()
                }
            }
        }
    });
    $("#et_ul_form_wallpaper input[type=radio]").click(function() {
        $("#" + $("#et_ul_form_wallpaper li.prop_selected input").attr("id") + "_tab").hide();
        $("#et_ul_form_wallpaper li.prop_selected").removeClass("prop_selected");
        $(this).parent().addClass("prop_selected");
        $("#" + $(this).attr("id") + "_tab").fadeIn();
        var b = $(this).attr("id");
        if (b == "et_form_wallpaper_color") {
            a.wallpaper_bg_type = "color";
            if (a.wallpaper_bg_color == "transparent") {
                $("#et_form_wallpaper_minicolor_input").miniColors("value", "");
                $("#et_theme_preview").css("background-color", "transparent");
                $("#et_theme_preview").css("background-image", "url('images/icons/transparent.png')");
                $("#et_theme_preview").css("background-repeat", "repeat");
                $("#et_form_wallpaper_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
                $("#et_form_wallpaper_minicolor_box").css("background-repeat", "repeat")
            } else {
                $("#et_theme_preview").css("background-image", "");
                $("#et_form_wallpaper_minicolor_box").css("background-image", "");
                $("#et_form_wallpaper_minicolor_box").css("background-color", a.wallpaper_bg_color);
                $("#et_theme_preview").css("background-color", a.wallpaper_bg_color)
            }
        } else {
            if (b == "et_form_wallpaper_pattern") {
                a.wallpaper_bg_type = "pattern";
                $("#et_theme_preview").css("background-image", "url('images/form_resources/" + a.wallpaper_bg_pattern + "')");
                $("#et_theme_preview").css("background-repeat", "repeat");
                $("#et_form_wallpaper_pattern_box").css("background-image", "url('images/form_resources/" + a.wallpaper_bg_pattern + "')");
                $("#et_form_wallpaper_pattern_box").css("background-repeat", "repeat")
            } else {
                if (b == "et_form_wallpaper_custom") {
                    a.wallpaper_bg_type = "custom";
                    $("#et_theme_preview").css("background-color", "#ececec");
                    $("#et_theme_preview").css("background-image", "url('" + a.wallpaper_bg_custom + "')");
                    $("#et_theme_preview").css("background-repeat", "repeat")
                }
            }
        }
    });
    $("#et_form_wallpaper_color_tab").delegate("li", "click", function(c) {
        $("#et_form_wallpaper_color_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        if ($(this).css("background-color") == "transparent") {
            $("#et_form_wallpaper_minicolor_input").miniColors("value", "");
            $("#et_theme_preview").css("background-color", "transparent");
            $("#et_theme_preview").css("background-image", "url('images/icons/transparent.png')");
            $("#et_theme_preview").css("background-repeat", "repeat");
            $("#et_form_wallpaper_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
            $("#et_form_wallpaper_minicolor_box").css("background-repeat", "repeat");
            a.wallpaper_bg_color = "transparent"
        } else {
            $("#et_theme_preview").css("background-image", "");
            $("#et_form_wallpaper_minicolor_box").css("background-image", "");
            var b = rgb2hex($(this).css("background-color"));
            $("#et_form_wallpaper_minicolor_input").miniColors("value", b);
            a.wallpaper_bg_color = b
        }
    });
    $("#et_form_wallpaper_pattern_tab").delegate("li", "click", function(b) {
        $("#et_form_wallpaper_pattern_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        $("#et_theme_preview").css("background-image", $(this).css("background-image"));
        $("#et_theme_preview").css("background-repeat", "repeat");
        $("#et_form_wallpaper_pattern_box").css("background-image", $(this).css("background-image"));
        $("#et_form_wallpaper_pattern_box").css("background-repeat", "repeat");
        $("#et_form_wallpaper_pattern_number").text("#" + ($(this).index() + 1));
        a.wallpaper_bg_pattern = $(this).data("pattern")
    });
    $("#et_form_wallpaper_minicolor_input").miniColors({
        change: function(c, b) {
            $("#et_theme_preview").css("background-image", "");
            $("#et_theme_preview").css("background-repeat", "no-repeat");
            $("#et_form_wallpaper_minicolor_box").css("background-image", "");
            $("#et_form_wallpaper_minicolor_box").css("background-repeat", "no-repeat");
            $("#et_form_wallpaper_minicolor_box").css("background-color", c);
            $("#et_theme_preview").css("background-color", c);
            a.wallpaper_bg_color = c
        }
    });
    $("#et_wallpaper_custom_bg_submit").click(function() {
        $("#et_theme_preview").css("background-color", "#ececec");
        $("#et_theme_preview").css("background-image", "url('" + $("#et_wallpaper_custom_bg").val() + "')");
        $("#et_theme_preview").css("background-repeat", "repeat");
        a.wallpaper_bg_custom = $("#et_wallpaper_custom_bg").val()
    });
    $("#dropui-bg-header a.dropui-tab").click(function() {
        $("#dropui-bg-main,#dropui-bg-header,#dropui-bg-form,#dropui-bg-highlight,#dropui-bg-guidelines,#dropui-bg-field").removeClass("hovered");
        $("#dropui-bg-main div.dropui-content,#dropui-bg-header div.dropui-content,#dropui-bg-form div.dropui-content,#dropui-bg-highlight div.dropui-content,#dropui-bg-guidelines div.dropui-content,#dropui-bg-field div.dropui-content").hide();
        $(this).parent().addClass("hovered");
        $(this).next().show();
        $("#et_ul_form_headerbg li.prop_selected").removeClass("prop_selected");
        $("#et_form_headerbg_content > div").hide();
        $("#et_form_headerbg_minicolor_box").css("background-image", "");
        $("#et_form_headerbg_minicolor_box").css("background-color", a.header_bg_color);
        $("#et_form_headerbg_pattern_box").css("background-image", "url('images/form_resources/" + a.header_bg_pattern + "')");
        $("#et_form_headerbg_pattern_box").css("background-repeat", "repeat");
        var b = $('#et_form_headerbg_pattern_tab ul.et_pattern_picker > li[data-pattern="' + a.header_bg_pattern + '"]').index() + 1;
        $("#et_form_headerbg_pattern_number").text("#" + b);
        $("#et_form_headerbg_pattern_tab ul li:eq(" + (b - 1) + ")").addClass("picker_selected");
        $("#et_headerbg_custom_bg").val(a.header_bg_custom);
        if (a.header_bg_type == "color") {
            if (a.header_bg_color == "transparent") {
                $("#et_form_headerbg_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
                $("#et_form_headerbg_minicolor_box").css("background-repeat", "repeat");
                $("#et_form_headerbg_color_tab ul li:eq(0)").addClass("picker_selected")
            } else {
                $("#et_form_headerbg_minicolor_input").miniColors("value", a.header_bg_color)
            }
            $("#et_form_headerbg_color").prop("checked", true);
            $("#et_form_headerbg_color").parent().addClass("prop_selected");
            $("#et_form_headerbg_color_tab").show()
        } else {
            if (a.header_bg_type == "pattern") {
                $("#et_form_headerbg_pattern").prop("checked", true);
                $("#et_form_headerbg_pattern").parent().addClass("prop_selected");
                $("#et_form_headerbg_pattern_tab").show()
            } else {
                if (a.header_bg_type == "custom") {
                    $("#et_form_headerbg_custom").prop("checked", true);
                    $("#et_form_headerbg_custom").parent().addClass("prop_selected");
                    $("#et_form_headerbg_custom_tab").show()
                }
            }
        }
    });
    $("#et_ul_form_headerbg input[type=radio]").click(function() {
        $("#" + $("#et_ul_form_headerbg li.prop_selected input").attr("id") + "_tab").hide();
        $("#et_ul_form_headerbg li.prop_selected").removeClass("prop_selected");
        $(this).parent().addClass("prop_selected");
        $("#" + $(this).attr("id") + "_tab").fadeIn();
        var b = $(this).attr("id");
        if (b == "et_form_headerbg_color") {
            a.header_bg_type = "color";
            $("#form_header_preview").css("background-image", "");
            if (a.header_bg_color == "transparent") {
                $("#et_form_headerbg_minicolor_input").miniColors("value", "");
                $("#et_form_headerbg_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
                $("#et_form_headerbg_minicolor_box").css("background-repeat", "repeat")
            } else {
                $("#et_form_headerbg_minicolor_box").css("background-image", "");
                $("#et_form_headerbg_minicolor_box").css("background-color", a.header_bg_color)
            }
            $("#form_header_preview").css("background-color", a.header_bg_color)
        } else {
            if (b == "et_form_headerbg_pattern") {
                a.header_bg_type = "pattern";
                $("#form_header_preview").css("background-image", "url('images/form_resources/" + a.header_bg_pattern + "')");
                $("#form_header_preview").css("background-repeat", "repeat");
                $("#et_form_headerbg_pattern_box").css("background-image", "url('images/form_resources/" + a.header_bg_pattern + "')");
                $("#et_form_headerbg_pattern_box").css("background-repeat", "repeat")
            } else {
                if (b == "et_form_headerbg_custom") {
                    a.header_bg_type = "custom";
                    $("#form_header_preview").css("background-color", "#ececec");
                    $("#form_header_preview").css("background-image", "url('" + a.header_bg_custom + "')");
                    $("#form_header_preview").css("background-repeat", "repeat")
                }
            }
        }
    });
    $("#et_form_headerbg_color_tab").delegate("li", "click", function(c) {
        $("#et_form_headerbg_color_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        if ($(this).css("background-color") == "transparent") {
            $("#et_form_headerbg_minicolor_input").miniColors("value", "");
            $("#form_header_preview").css("background-color", "transparent");
            $("#et_form_headerbg_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
            $("#et_form_headerbg_minicolor_box").css("background-repeat", "repeat");
            a.header_bg_color = "transparent"
        } else {
            $("#form_header_preview").css("background-image", "");
            $("#et_form_headerbg_minicolor_box").css("background-image", "");
            var b = rgb2hex($(this).css("background-color"));
            $("#et_form_headerbg_minicolor_input").miniColors("value", b);
            a.header_bg_color = b
        }
    });
    $("#et_form_headerbg_pattern_tab").delegate("li", "click", function(b) {
        $("#et_form_headerbg_pattern_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        $("#form_header_preview").css("background-image", $(this).css("background-image"));
        $("#form_header_preview").css("background-repeat", "repeat");
        $("#et_form_headerbg_pattern_box").css("background-image", $(this).css("background-image"));
        $("#et_form_headerbg_pattern_box").css("background-repeat", "repeat");
        $("#et_form_headerbg_pattern_number").text("#" + ($(this).index() + 1));
        a.header_bg_pattern = $(this).data("pattern")
    });
    $("#et_form_headerbg_minicolor_input").miniColors({
        change: function(c, b) {
            $("#form_header_preview").css("background-image", "");
            $("#form_header_preview").css("background-repeat", "no-repeat");
            $("#et_form_headerbg_minicolor_box").css("background-image", "");
            $("#et_form_headerbg_minicolor_box").css("background-repeat", "no-repeat");
            $("#et_form_headerbg_minicolor_box").css("background-color", c);
            $("#form_header_preview").css("background-color", c);
            a.header_bg_color = c
        }
    });
    $("#et_headerbg_custom_bg_submit").click(function() {
        $("#form_header_preview").css("background-color", "#ececec");
        $("#form_header_preview").css("background-image", "url('" + $("#et_headerbg_custom_bg").val() + "')");
        $("#form_header_preview").css("background-repeat", "repeat");
        a.header_bg_custom = $("#et_headerbg_custom_bg").val()
    });
    $("#dropui-bg-form a.dropui-tab").click(function() {
        $("#dropui-bg-main,#dropui-bg-header,#dropui-bg-form,#dropui-bg-highlight,#dropui-bg-guidelines,#dropui-bg-field").removeClass("hovered");
        $("#dropui-bg-main div.dropui-content,#dropui-bg-header div.dropui-content,#dropui-bg-form div.dropui-content,#dropui-bg-highlight div.dropui-content,#dropui-bg-guidelines div.dropui-content,#dropui-bg-field div.dropui-content").hide();
        $(this).parent().addClass("hovered");
        $(this).next().show();
        $("#et_ul_form_formbg li.prop_selected").removeClass("prop_selected");
        $("#et_form_formbg_content > div").hide();
        $("#et_form_formbg_minicolor_box").css("background-image", "");
        $("#et_form_formbg_minicolor_box").css("background-color", a.form_bg_color);
        $("#et_form_formbg_pattern_box").css("background-image", "url('images/form_resources/" + a.form_bg_pattern + "')");
        $("#et_form_formbg_pattern_box").css("background-repeat", "repeat");
        var b = $('#et_form_formbg_pattern_tab ul.et_pattern_picker > li[data-pattern="' + a.form_bg_pattern + '"]').index() + 1;
        $("#et_form_formbg_pattern_number").text("#" + b);
        $("#et_form_formbg_pattern_tab ul li:eq(" + (b - 1) + ")").addClass("picker_selected");
        $("#et_formbg_custom_bg").val(a.form_bg_custom);
        if (a.form_bg_type == "color") {
            if (a.form_bg_color == "transparent") {
                $("#et_form_formbg_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
                $("#et_form_formbg_minicolor_box").css("background-repeat", "repeat");
                $("#et_form_formbg_color_tab ul li:eq(0)").addClass("picker_selected")
            } else {
                $("#et_form_formbg_minicolor_input").miniColors("value", a.form_bg_color)
            }
            $("#et_form_formbg_color").prop("checked", true);
            $("#et_form_formbg_color").parent().addClass("prop_selected");
            $("#et_form_formbg_color_tab").show()
        } else {
            if (a.form_bg_type == "pattern") {
                $("#et_form_formbg_pattern").prop("checked", true);
                $("#et_form_formbg_pattern").parent().addClass("prop_selected");
                $("#et_form_formbg_pattern_tab").show()
            } else {
                if (a.form_bg_type == "custom") {
                    $("#et_form_formbg_custom").prop("checked", true);
                    $("#et_form_formbg_custom").parent().addClass("prop_selected");
                    $("#et_form_formbg_custom_tab").show()
                }
            }
        }
    });
    $("#et_ul_form_formbg input[type=radio]").click(function() {
        $("#" + $("#et_ul_form_formbg li.prop_selected input").attr("id") + "_tab").hide();
        $("#et_ul_form_formbg li.prop_selected").removeClass("prop_selected");
        $(this).parent().addClass("prop_selected");
        $("#" + $(this).attr("id") + "_tab").fadeIn();
        var b = $(this).attr("id");
        if (b == "et_form_formbg_color") {
            a.form_bg_type = "color";
            $("#form_container").css("background-image", "");
            if (a.form_bg_color == "transparent") {
                $("#et_form_formbg_minicolor_input").miniColors("value", "");
                $("#et_form_formbg_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
                $("#et_form_formbg_minicolor_box").css("background-repeat", "repeat")
            } else {
                $("#et_form_formbg_minicolor_box").css("background-image", "");
                $("#et_form_formbg_minicolor_box").css("background-color", a.form_bg_color)
            }
            $("#form_container").css("background-color", a.form_bg_color)
        } else {
            if (b == "et_form_formbg_pattern") {
                a.form_bg_type = "pattern";
                $("#form_container").css("background-image", "url('images/form_resources/" + a.form_bg_pattern + "')");
                $("#form_container").css("background-repeat", "repeat");
                $("#et_form_formbg_pattern_box").css("background-image", "url('images/form_resources/" + a.form_bg_pattern + "')");
                $("#et_form_formbg_pattern_box").css("background-repeat", "repeat")
            } else {
                if (b == "et_form_formbg_custom") {
                    a.form_bg_type = "custom";
                    $("#form_container").css("background-color", "#ececec");
                    $("#form_container").css("background-image", "url('" + a.form_bg_custom + "')");
                    $("#form_container").css("background-repeat", "repeat")
                }
            }
        }
    });
    $("#et_form_formbg_color_tab").delegate("li", "click", function(c) {
        $("#et_form_formbg_color_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        if ($(this).css("background-color") == "transparent") {
            $("#et_form_formbg_minicolor_input").miniColors("value", "");
            $("#form_container").css("background-color", "transparent");
            $("#et_form_formbg_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
            $("#et_form_formbg_minicolor_box").css("background-repeat", "repeat");
            a.form_bg_color = "transparent"
        } else {
            $("#form_container").css("background-image", "");
            $("#et_form_formbg_minicolor_box").css("background-image", "");
            var b = rgb2hex($(this).css("background-color"));
            $("#et_form_formbg_minicolor_input").miniColors("value", b);
            a.form_bg_color = b
        }
    });
    $("#et_form_formbg_pattern_tab").delegate("li", "click", function(b) {
        $("#et_form_formbg_pattern_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        $("#form_container").css("background-image", $(this).css("background-image"));
        $("#form_container").css("background-repeat", "repeat");
        $("#et_form_formbg_pattern_box").css("background-image", $(this).css("background-image"));
        $("#et_form_formbg_pattern_box").css("background-repeat", "repeat");
        $("#et_form_formbg_pattern_number").text("#" + ($(this).index() + 1));
        a.form_bg_pattern = $(this).data("pattern")
    });
    $("#et_form_formbg_minicolor_input").miniColors({
        change: function(c, b) {
            $("#form_container").css("background-image", "");
            $("#form_container").css("background-repeat", "no-repeat");
            $("#et_form_formbg_minicolor_box").css("background-image", "");
            $("#et_form_formbg_minicolor_box").css("background-repeat", "no-repeat");
            $("#et_form_formbg_minicolor_box").css("background-color", c);
            $("#form_container").css("background-color", c);
            a.form_bg_color = c
        }
    });
    $("#et_formbg_custom_bg_submit").click(function() {
        $("#form_container").css("background-color", "#ececec");
        $("#form_container").css("background-image", "url('" + $("#et_formbg_custom_bg").val() + "')");
        $("#form_container").css("background-repeat", "repeat");
        a.form_bg_custom = $("#et_formbg_custom_bg").val()
    });
    $("#dropui-bg-highlight a.dropui-tab").click(function() {
        $("#dropui-bg-main,#dropui-bg-header,#dropui-bg-form,#dropui-bg-highlight,#dropui-bg-guidelines,#dropui-bg-field").removeClass("hovered");
        $("#dropui-bg-main div.dropui-content,#dropui-bg-header div.dropui-content,#dropui-bg-form div.dropui-content,#dropui-bg-highlight div.dropui-content,#dropui-bg-guidelines div.dropui-content,#dropui-bg-field div.dropui-content").hide();
        $(this).parent().addClass("hovered");
        $(this).next().show();
        $("#et_ul_form_highlightbg li.prop_selected").removeClass("prop_selected");
        $("#et_form_highlightbg_content > div").hide();
        $("#et_form_highlightbg_minicolor_box").css("background-image", "");
        $("#et_form_highlightbg_minicolor_box").css("background-color", a.highlight_bg_color);
        $("#et_form_highlightbg_pattern_box").css("background-image", "url('images/form_resources/" + a.highlight_bg_pattern + "')");
        $("#et_form_highlightbg_pattern_box").css("background-repeat", "repeat");
        var b = $('#et_form_highlightbg_pattern_tab ul.et_pattern_picker > li[data-pattern="' + a.highlight_bg_pattern + '"]').index() + 1;
        $("#et_form_highlightbg_pattern_number").text("#" + b);
        $("#et_form_highlightbg_pattern_tab ul li:eq(" + (b - 1) + ")").addClass("picker_selected");
        $("#et_highlightbg_custom_bg").val(a.highlight_bg_custom);
        if (a.highlight_bg_type == "color") {
            if (a.highlight_bg_color == "transparent") {
                $("#et_form_highlightbg_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
                $("#et_form_highlightbg_minicolor_box").css("background-repeat", "repeat");
                $("#et_form_highlightbg_color_tab ul li:eq(0)").addClass("picker_selected")
            } else {
                $("#et_form_highlightbg_minicolor_input").miniColors("value", a.highlight_bg_color)
            }
            $("#et_form_highlightbg_color").prop("checked", true);
            $("#et_form_highlightbg_color").parent().addClass("prop_selected");
            $("#et_form_highlightbg_color_tab").show()
        } else {
            if (a.highlight_bg_type == "pattern") {
                $("#et_form_highlightbg_pattern").prop("checked", true);
                $("#et_form_highlightbg_pattern").parent().addClass("prop_selected");
                $("#et_form_highlightbg_pattern_tab").show()
            } else {
                if (a.highlight_bg_type == "custom") {
                    $("#et_form_highlightbg_custom").prop("checked", true);
                    $("#et_form_highlightbg_custom").parent().addClass("prop_selected");
                    $("#et_form_highlightbg_custom_tab").show()
                }
            }
        }
    });
    $("#et_ul_form_highlightbg input[type=radio]").click(function() {
        $("#" + $("#et_ul_form_highlightbg li.prop_selected input").attr("id") + "_tab").hide();
        $("#et_ul_form_highlightbg li.prop_selected").removeClass("prop_selected");
        $(this).parent().addClass("prop_selected");
        $("#" + $(this).attr("id") + "_tab").fadeIn();
        var b = $(this).attr("id");
        if (b == "et_form_highlightbg_color") {
            a.highlight_bg_type = "color";
            $("#li_fields li.highlighted").css("background-image", "");
            if (a.highlight_bg_color == "transparent") {
                $("#et_form_highlightbg_minicolor_input").miniColors("value", "");
                $("#et_form_highlightbg_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
                $("#et_form_highlightbg_minicolor_box").css("background-repeat", "repeat")
            } else {
                $("#et_form_highlightbg_minicolor_box").css("background-image", "");
                $("#et_form_highlightbg_minicolor_box").css("background-color", a.highlight_bg_color)
            }
            $("#li_fields li.highlighted").css("background-color", a.highlight_bg_color)
        } else {
            if (b == "et_form_highlightbg_pattern") {
                a.highlight_bg_type = "pattern";
                $("#li_fields li.highlighted").css("background-image", "url('images/form_resources/" + a.highlight_bg_pattern + "')");
                $("#li_fields li.highlighted").css("background-repeat", "repeat");
                $("#et_form_highlightbg_pattern_box").css("background-image", "url('images/form_resources/" + a.highlight_bg_pattern + "')");
                $("#et_form_highlightbg_pattern_box").css("background-repeat", "repeat")
            } else {
                if (b == "et_form_highlightbg_custom") {
                    a.highlight_bg_type = "custom";
                    $("#li_fields li.highlighted").css("background-color", "#ececec");
                    $("#li_fields li.highlighted").css("background-image", "url('" + a.highlight_bg_custom + "')");
                    $("#li_fields li.highlighted").css("background-repeat", "repeat")
                }
            }
        }
    });
    $("#et_form_highlightbg_color_tab").delegate("li", "click", function(c) {
        $("#et_form_highlightbg_color_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        if ($(this).css("background-color") == "transparent") {
            $("#et_form_highlightbg_minicolor_input").miniColors("value", "");
            $("#li_fields li.highlighted").css("background-color", "transparent");
            $("#et_form_highlightbg_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
            $("#et_form_highlightbg_minicolor_box").css("background-repeat", "repeat");
            a.highlight_bg_color = "transparent"
        } else {
            $("#li_fields li.highlighted").css("background-image", "");
            $("#et_form_highlightbg_minicolor_box").css("background-image", "");
            var b = rgb2hex($(this).css("background-color"));
            $("#et_form_highlightbg_minicolor_input").miniColors("value", b);
            a.highlight_bg_color = b
        }
    });
    $("#et_form_highlightbg_pattern_tab").delegate("li", "click", function(b) {
        $("#et_form_highlightbg_pattern_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        $("#li_fields li.highlighted").css("background-image", $(this).css("background-image"));
        $("#li_fields li.highlighted").css("background-repeat", "repeat");
        $("#et_form_highlightbg_pattern_box").css("background-image", $(this).css("background-image"));
        $("#et_form_highlightbg_pattern_box").css("background-repeat", "repeat");
        $("#et_form_highlightbg_pattern_number").text("#" + ($(this).index() + 1));
        a.highlight_bg_pattern = $(this).data("pattern")
    });
    $("#et_form_highlightbg_minicolor_input").miniColors({
        change: function(c, b) {
            $("#li_fields li.highlighted").css("background-image", "");
            $("#li_fields li.highlighted").css("background-repeat", "no-repeat");
            $("#et_form_highlightbg_minicolor_box").css("background-image", "");
            $("#et_form_highlightbg_minicolor_box").css("background-repeat", "no-repeat");
            $("#et_form_highlightbg_minicolor_box").css("background-color", c);
            $("#li_fields li.highlighted").css("background-color", c);
            a.highlight_bg_color = c
        }
    });
    $("#et_highlightbg_custom_bg_submit").click(function() {
        $("#li_fields li.highlighted").css("background-color", "#ececec");
        $("#li_fields li.highlighted").css("background-image", "url('" + $("#et_highlightbg_custom_bg").val() + "')");
        $("#li_fields li.highlighted").css("background-repeat", "repeat");
        a.highlight_bg_custom = $("#et_highlightbg_custom_bg").val()
    });
    $("#dropui-bg-guidelines a.dropui-tab").click(function() {
        $("#dropui-bg-main,#dropui-bg-header,#dropui-bg-form,#dropui-bg-highlight,#dropui-bg-guidelines,#dropui-bg-field").removeClass("hovered");
        $("#dropui-bg-main div.dropui-content,#dropui-bg-header div.dropui-content,#dropui-bg-form div.dropui-content,#dropui-bg-highlight div.dropui-content,#dropui-bg-guidelines div.dropui-content,#dropui-bg-field div.dropui-content").hide();
        $(this).parent().addClass("hovered");
        $(this).next().show();
        $("#et_ul_form_guidelinesbg li.prop_selected").removeClass("prop_selected");
        $("#et_form_guidelinesbg_content > div").hide();
        $("#et_form_guidelinesbg_minicolor_box").css("background-image", "");
        $("#et_form_guidelinesbg_minicolor_box").css("background-color", a.guidelines_bg_color);
        $("#et_form_guidelinesbg_pattern_box").css("background-image", "url('images/form_resources/" + a.guidelines_bg_pattern + "')");
        $("#et_form_guidelinesbg_pattern_box").css("background-repeat", "repeat");
        var b = $('#et_form_guidelinesbg_pattern_tab ul.et_pattern_picker > li[data-pattern="' + a.guidelines_bg_pattern + '"]').index() + 1;
        $("#et_form_guidelinesbg_pattern_number").text("#" + b);
        $("#et_form_guidelinesbg_pattern_tab ul li:eq(" + (b - 1) + ")").addClass("picker_selected");
        $("#et_guidelinesbg_custom_bg").val(a.guidelines_bg_custom);
        if (a.guidelines_bg_type == "color") {
            if (a.guidelines_bg_color == "transparent") {
                $("#et_form_guidelinesbg_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
                $("#et_form_guidelinesbg_minicolor_box").css("background-repeat", "repeat");
                $("#et_form_guidelinesbg_color_tab ul li:eq(0)").addClass("picker_selected")
            } else {
                $("#et_form_guidelinesbg_minicolor_input").miniColors("value", a.guidelines_bg_color)
            }
            $("#et_form_guidelinesbg_color").prop("checked", true);
            $("#et_form_guidelinesbg_color").parent().addClass("prop_selected");
            $("#et_form_guidelinesbg_color_tab").show()
        } else {
            if (a.guidelines_bg_type == "pattern") {
                $("#et_form_guidelinesbg_pattern").prop("checked", true);
                $("#et_form_guidelinesbg_pattern").parent().addClass("prop_selected");
                $("#et_form_guidelinesbg_pattern_tab").show()
            } else {
                if (a.guidelines_bg_type == "custom") {
                    $("#et_form_guidelinesbg_custom").prop("checked", true);
                    $("#et_form_guidelinesbg_custom").parent().addClass("prop_selected");
                    $("#et_form_guidelinesbg_custom_tab").show()
                }
            }
        }
    });
    $("#et_ul_form_guidelinesbg input[type=radio]").click(function() {
        $("#" + $("#et_ul_form_guidelinesbg li.prop_selected input").attr("id") + "_tab").hide();
        $("#et_ul_form_guidelinesbg li.prop_selected").removeClass("prop_selected");
        $(this).parent().addClass("prop_selected");
        $("#" + $(this).attr("id") + "_tab").fadeIn();
        var b = $(this).attr("id");
        if (b == "et_form_guidelinesbg_color") {
            a.guidelines_bg_type = "color";
            $("#li_fields p.guidelines").css("background-image", "");
            if (a.guidelines_bg_color == "transparent") {
                $("#et_form_guidelinesbg_minicolor_input").miniColors("value", "");
                $("#et_form_guidelinesbg_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
                $("#et_form_guidelinesbg_minicolor_box").css("background-repeat", "repeat")
            } else {
                $("#et_form_guidelinesbg_minicolor_box").css("background-image", "");
                $("#et_form_guidelinesbg_minicolor_box").css("background-color", a.guidelines_bg_color)
            }
            $("#li_fields p.guidelines").css("background-color", a.guidelines_bg_color)
        } else {
            if (b == "et_form_guidelinesbg_pattern") {
                a.guidelines_bg_type = "pattern";
                $("#li_fields p.guidelines").css("background-image", "url('images/form_resources/" + a.guidelines_bg_pattern + "')");
                $("#li_fields p.guidelines").css("background-repeat", "repeat");
                $("#et_form_guidelinesbg_pattern_box").css("background-image", "url('images/form_resources/" + a.guidelines_bg_pattern + "')");
                $("#et_form_guidelinesbg_pattern_box").css("background-repeat", "repeat")
            } else {
                if (b == "et_form_guidelinesbg_custom") {
                    a.guidelines_bg_type = "custom";
                    $("#li_fields p.guidelines").css("background-color", "#ececec");
                    $("#li_fields p.guidelines").css("background-image", "url('" + a.guidelines_bg_custom + "')");
                    $("#li_fields p.guidelines").css("background-repeat", "repeat")
                }
            }
        }
    });
    $("#et_form_guidelinesbg_color_tab").delegate("li", "click", function(c) {
        $("#et_form_guidelinesbg_color_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        if ($(this).css("background-color") == "transparent") {
            $("#et_form_guidelinesbg_minicolor_input").miniColors("value", "");
            $("#li_fields p.guidelines").css("background-color", "transparent");
            $("#et_form_guidelinesbg_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
            $("#et_form_guidelinesbg_minicolor_box").css("background-repeat", "repeat");
            a.guidelines_bg_color = "transparent"
        } else {
            $("#li_fields p.guidelines").css("background-image", "");
            $("#et_form_guidelinesbg_minicolor_box").css("background-image", "");
            var b = rgb2hex($(this).css("background-color"));
            $("#et_form_guidelinesbg_minicolor_input").miniColors("value", b);
            a.guidelines_bg_color = b
        }
    });
    $("#et_form_guidelinesbg_pattern_tab").delegate("li", "click", function(b) {
        $("#et_form_guidelinesbg_pattern_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        $("#li_fields p.guidelines").css("background-image", $(this).css("background-image"));
        $("#li_fields p.guidelines").css("background-repeat", "repeat");
        $("#et_form_guidelinesbg_pattern_box").css("background-image", $(this).css("background-image"));
        $("#et_form_guidelinesbg_pattern_box").css("background-repeat", "repeat");
        $("#et_form_guidelinesbg_pattern_number").text("#" + ($(this).index() + 1));
        a.guidelines_bg_pattern = $(this).data("pattern")
    });
    $("#et_form_guidelinesbg_minicolor_input").miniColors({
        change: function(c, b) {
            $("#li_fields p.guidelines").css("background-image", "");
            $("#li_fields p.guidelines").css("background-repeat", "no-repeat");
            $("#et_form_guidelinesbg_minicolor_box").css("background-image", "");
            $("#et_form_guidelinesbg_minicolor_box").css("background-repeat", "no-repeat");
            $("#et_form_guidelinesbg_minicolor_box").css("background-color", c);
            $("#li_fields p.guidelines").css("background-color", c);
            a.guidelines_bg_color = c
        }
    });
    $("#et_guidelinesbg_custom_bg_submit").click(function() {
        $("#li_fields p.guidelines").css("background-color", "#ececec");
        $("#li_fields p.guidelines").css("background-image", "url('" + $("#et_guidelinesbg_custom_bg").val() + "')");
        $("#li_fields p.guidelines").css("background-repeat", "repeat");
        a.guidelines_bg_custom = $("#et_guidelinesbg_custom_bg").val()
    });
    $("#dropui-bg-field a.dropui-tab").click(function() {
        $("#dropui-bg-main,#dropui-bg-header,#dropui-bg-form,#dropui-bg-highlight,#dropui-bg-guidelines,#dropui-bg-field").removeClass("hovered");
        $("#dropui-bg-main div.dropui-content,#dropui-bg-header div.dropui-content,#dropui-bg-form div.dropui-content,#dropui-bg-highlight div.dropui-content,#dropui-bg-guidelines div.dropui-content,#dropui-bg-field div.dropui-content").hide();
        $(this).parent().addClass("hovered");
        $(this).next().show();
        $("#et_ul_form_fieldbg li.prop_selected").removeClass("prop_selected");
        $("#et_form_fieldbg_content > div").hide();
        $("#et_form_fieldbg_minicolor_box").css("background-image", "");
        $("#et_form_fieldbg_minicolor_box").css("background-color", a.field_bg_color);
        $("#et_form_fieldbg_pattern_box").css("background-image", "url('images/form_resources/" + a.field_bg_pattern + "')");
        $("#et_form_fieldbg_pattern_box").css("background-repeat", "repeat");
        var b = $('#et_form_fieldbg_pattern_tab ul.et_pattern_picker > li[data-pattern="' + a.field_bg_pattern + '"]').index() + 1;
        $("#et_form_fieldbg_pattern_number").text("#" + b);
        $("#et_form_fieldbg_pattern_tab ul li:eq(" + (b - 1) + ")").addClass("picker_selected");
        $("#et_fieldbg_custom_bg").val(a.field_bg_custom);
        if (a.field_bg_type == "color") {
            if (a.field_bg_color == "transparent") {
                $("#et_form_fieldbg_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
                $("#et_form_fieldbg_minicolor_box").css("background-repeat", "repeat");
                $("#et_form_fieldbg_color_tab ul li:eq(0)").addClass("picker_selected")
            } else {
                $("#et_form_fieldbg_minicolor_input").miniColors("value", a.field_bg_color)
            }
            $("#form_theme_preview :input").not(".submit_button").css("background-repeat", "repeat-x");
            $("#et_form_fieldbg_color").prop("checked", true);
            $("#et_form_fieldbg_color").parent().addClass("prop_selected");
            $("#et_form_fieldbg_color_tab").show()
        } else {
            if (a.field_bg_type == "pattern") {
                $("#et_form_fieldbg_pattern").prop("checked", true);
                $("#et_form_fieldbg_pattern").parent().addClass("prop_selected");
                $("#et_form_fieldbg_pattern_tab").show()
            } else {
                if (a.field_bg_type == "custom") {
                    $("#et_form_fieldbg_custom").prop("checked", true);
                    $("#et_form_fieldbg_custom").parent().addClass("prop_selected");
                    $("#et_form_fieldbg_custom_tab").show()
                }
            }
        }
    });
    $("#et_ul_form_fieldbg input[type=radio]").click(function() {
        $("#" + $("#et_ul_form_fieldbg li.prop_selected input").attr("id") + "_tab").hide();
        $("#et_ul_form_fieldbg li.prop_selected").removeClass("prop_selected");
        $(this).parent().addClass("prop_selected");
        $("#" + $(this).attr("id") + "_tab").fadeIn();
        var b = $(this).attr("id");
        if (b == "et_form_fieldbg_color") {
            a.field_bg_type = "color";
            if (a.field_bg_color == "transparent") {
                $("#et_form_fieldbg_minicolor_input").miniColors("value", "");
                $("#et_form_fieldbg_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
                $("#et_form_fieldbg_minicolor_box").css("background-repeat", "repeat")
            } else {
                $("#et_form_fieldbg_minicolor_box").css("background-image", "");
                $("#et_form_fieldbg_minicolor_box").css("background-color", a.field_bg_color)
            }
            $("#form_theme_preview :input").not(".submit_button").css("background-image", "");
            $("#form_theme_preview :input").not(".submit_button").css("background-repeat", "repeat-x");
            $("#form_theme_preview :input").not(".submit_button").css("background-color", a.field_bg_color)
        } else {
            if (b == "et_form_fieldbg_pattern") {
                a.field_bg_type = "pattern";
                $("#form_theme_preview :input").not(".submit_button").css("background-image", "url('images/form_resources/" + a.field_bg_pattern + "')");
                $("#form_theme_preview :input").not(".submit_button").css("background-repeat", "repeat");
                $("#et_form_fieldbg_pattern_box").css("background-image", "url('images/form_resources/" + a.field_bg_pattern + "')");
                $("#et_form_fieldbg_pattern_box").css("background-repeat", "repeat")
            } else {
                if (b == "et_form_fieldbg_custom") {
                    a.field_bg_type = "custom";
                    $("#form_theme_preview :input").not(".submit_button").css("background-color", "#ececec");
                    $("#form_theme_preview :input").not(".submit_button").css("background-image", "url('" + a.field_bg_custom + "')");
                    $("#form_theme_preview :input").not(".submit_button").css("background-repeat", "repeat")
                }
            }
        }
    });
    $("#et_form_fieldbg_color_tab").delegate("li", "click", function(c) {
        $("#et_form_fieldbg_color_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        if ($(this).css("background-color") == "transparent") {
            $("#et_form_fieldbg_minicolor_input").miniColors("value", "");
            $("#form_theme_preview :input").not(".submit_button").css("background-color", "transparent");
            $("#et_form_fieldbg_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
            $("#et_form_fieldbg_minicolor_box").css("background-repeat", "repeat");
            a.field_bg_color = "transparent"
        } else {
            $("#et_form_fieldbg_minicolor_box").css("background-image", "");
            var b = rgb2hex($(this).css("background-color"));
            $("#et_form_fieldbg_minicolor_input").miniColors("value", b);
            $("#form_theme_preview :input").not(".submit_button").css("background-image", "");
            $("#form_theme_preview :input").not(".submit_button").css("background-repeat", "repeat-x");
            a.field_bg_color = b
        }
    });
    $("#et_form_fieldbg_pattern_tab").delegate("li", "click", function(b) {
        $("#et_form_fieldbg_pattern_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        $("#form_theme_preview :input").not(".submit_button").css("background-image", $(this).css("background-image"));
        $("#form_theme_preview :input").not(".submit_button").css("background-repeat", "repeat");
        $("#et_form_fieldbg_pattern_box").css("background-image", $(this).css("background-image"));
        $("#et_form_fieldbg_pattern_box").css("background-repeat", "repeat");
        $("#et_form_fieldbg_pattern_number").text("#" + ($(this).index() + 1));
        a.field_bg_pattern = $(this).data("pattern")
    });
    $("#et_form_fieldbg_minicolor_input").miniColors({
        change: function(c, b) {
            $("#form_theme_preview :input").not(".submit_button").css("background-image", "");
            $("#form_theme_preview :input").not(".submit_button").css("background-repeat", "no-repeat");
            $("#et_form_fieldbg_minicolor_box").css("background-image", "");
            $("#et_form_fieldbg_minicolor_box").css("background-repeat", "no-repeat");
            $("#et_form_fieldbg_minicolor_box").css("background-color", c);
            $("#form_theme_preview :input").not(".submit_button").css("background-color", c);
            a.field_bg_color = c
        }
    });
    $("#et_fieldbg_custom_bg_submit").click(function() {
        $("#form_theme_preview :input").not(".submit_button").css("background-color", "#ececec");
        $("#form_theme_preview :input").not(".submit_button").css("background-image", "url('" + $("#et_fieldbg_custom_bg").val() + "')");
        $("#form_theme_preview :input").not(".submit_button").css("background-repeat", "repeat");
        a.field_bg_custom = $("#et_fieldbg_custom_bg").val()
    });
    $("ul.et_font_picker li.li_show_more").click(function() {
        $("li.li_show_more").children("span").text("Loading");
        $("li.li_show_more").children("img").attr("src", "images/loader_small_grey.gif");
        $.ajax({
            type: "POST",
            async: true,
            url: "get_font_list.php",
            data: {
                start_id: $("#header").data("last_font_id") + 1,
                list_length: 20
            },
            cache: false,
            global: true,
            dataType: "json",
            error: function(d, b, c) {
                $("li.li_show_more").children("span").text("Show More Fonts");
                $("li.li_show_more").children("img").attr("src", "images/icons/arrow_down.png")
            },
            success: function(e) {
                if (e.status == "ok") {
                    var d = $(e.markup);
                    $("li.li_show_more").before(d);
                    $("li.li_show_more").children("span").text("Show More Fonts");
                    $("li.li_show_more").children("img").attr("src", "images/icons/arrow_down.png");
                    var b = e.font_styles;
                    var c = $("#header").data("font_styles");
                    $.each(b, function(g, f) {
                        $("#header").data("font_" + g, f)
                    });
                    $("#header").data("last_font_id", e.last_font_id);
                    $("head").append(e.font_css_markup);
                    if (e.list_end == true) {
                        $("li.li_show_more").remove()
                    }
                } else {
                    $("li.li_show_more").children("span").text("Show More Fonts");
                    $("li.li_show_more").children("img").attr("src", "images/icons/arrow_down.png")
                }
            }
        })
    });
    $("#dropui-typo-form-title a.dropui-tab").click(function() {
        $("#dropui-typo-form-title,#dropui-typo-form-desc,#dropui-typo-field-title,#dropui-typo-guidelines,#dropui-typo-section-title,#dropui-typo-section-desc,#dropui-typo-field-text").removeClass("hovered");
        $("#dropui-typo-form-title div.dropui-content,#dropui-typo-form-desc div.dropui-content,#dropui-typo-field-title div.dropui-content,#dropui-typo-guidelines div.dropui-content,#dropui-typo-section-title div.dropui-content,#dropui-typo-section-desc div.dropui-content,#dropui-typo-field-text div.dropui-content").hide();
        $(this).parent().addClass("hovered");
        $(this).next().show();
        if (a.form_title_font_type == "" || a.form_title_font_type == "Lucida Grande") {
            $("#et_li_typo_form_title_font_tab li:eq(0)").addClass("font_selected")
        }
        $("#et_form_title_font_preview_box").css("font-family", a.form_title_font_type);
        $("#et_form_title_font_preview_name").text(a.form_title_font_type);
        $("#et_typo_form_title_minicolor_input").miniColors("value", a.form_title_font_color)
    });
    $("#et_typo_form_title_minicolor_input").miniColors({
        change: function(c, b) {
            $("#form_title_preview").css("color", c);
            $("#et_typo_form_title_minicolor_box").css("background-color", c);
            a.form_title_font_color = c
        }
    });
    $("#et_li_typo_form_title_color_tab").delegate("li", "click", function(c) {
        $("#et_li_typo_form_title_color_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        if ($(this).css("background-color") == "transparent") {
            $("#et_typo_form_title_minicolor_input").miniColors("value", "");
            $("#form_title_preview").css("color", "transparent");
            $("#et_typo_form_title_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
            $("#et_typo_form_title_minicolor_box").css("background-repeat", "repeat");
            a.form_title_font_color = "transparent"
        } else {
            $("#et_typo_form_title_minicolor_box").css("background-image", "");
            var b = rgb2hex($(this).css("background-color"));
            $("#et_typo_form_title_minicolor_input").miniColors("value", b)
        }
    });
    $("#et_ul_typo_form_title").delegate("li", "click", function(g) {
        $("#" + $("#et_ul_typo_form_title li.tab_selected").attr("id") + "_tab").hide();
        $("#et_ul_typo_form_title li.tab_selected").removeClass("tab_selected");
        $(this).addClass("tab_selected");
        var d = $(this).attr("id");
        if (d == "et_li_typo_form_title_style") {
            $("#et_typo_form_title_content").animate({
                width: "295px"
            }, {
                duration: 200,
                queue: false
            });
            $("#dropui-typo-form-title div.dropui-content").animate({
                width: "350px"
            }, {
                duration: 200,
                queue: false
            });
            var h = a.form_title_font_type.replace(/ /g, "").toLowerCase();
            var b = $("#header").data("font_" + h);
            if (b == null) {
                b = {
                    "400": "Normal",
                    "400-italic": "Italic",
                    "700": "Bold"
                }
            }
            var c = "";
            $.each(b, function(i, e) {
                c += '<li><input type="radio" name="et_typo_form_title_style_radio" id="et_typo_form_title_style_radio' + i + '" value="' + i + '" /> <label for="et_typo_form_title_style_radio' + i + '">' + e + "</label></li>"
            });
            $("#et_ul_typo_form_title_style > li").not(".dummy_li").remove();
            $("#et_ul_typo_form_title_style").append(c);
            if (a.form_title_font_weight == 0) {
                a.form_title_font_weight = 400
            }
            var f = a.form_title_font_weight;
            if (a.form_title_font_style == "italic") {
                f = f + "-italic"
            }
            $("#et_ul_typo_form_title_style input[value='" + f + "']").prop("checked", true)
        } else {
            if (d == "et_li_typo_form_title_size") {
                $("#et_typo_form_title_content").animate({
                    width: "295px"
                }, {
                    duration: 200,
                    queue: false
                });
                $("#dropui-typo-form-title div.dropui-content").animate({
                    width: "350px"
                }, {
                    duration: 200,
                    queue: false
                });
                if (a.form_title_font_size == "") {
                    a.form_title_font_size = "160%"
                }
                $("#et_typo_form_title_size_pickerbox li[data-fsize='" + a.form_title_font_size + "']").addClass("box_selected")
            } else {
                if (d == "et_li_typo_form_title_color") {
                    $("#et_typo_form_title_content").animate({
                        width: "305px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-typo-form-title div.dropui-content").animate({
                        width: "360px"
                    }, {
                        duration: 200,
                        queue: false
                    })
                } else {
                    $("#et_typo_form_title_content").animate({
                        width: "450px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-typo-form-title div.dropui-content").animate({
                        width: "507px"
                    }, {
                        duration: 200,
                        queue: false
                    })
                }
            }
        }
        $("#" + d + "_tab").fadeIn()
    });
    $("#et_li_typo_form_title_font_tab").delegate("li:not(.li_show_more)", "click", function(b) {
        $("#form_title_preview").css("font-family", $(this).children("div.font_picker_preview").css("font-family"));
        $("#form_title_preview").css("font-weight", "400");
        $("#form_title_preview").css("font-style", "normal");
        $(this).siblings().removeClass("font_selected");
        $(this).addClass("font_selected");
        $("#et_form_title_font_preview_box").css("font-family", $(this).children("div.font_picker_preview").css("font-family"));
        $("#et_form_title_font_preview_name").text($(this).children("div.font_picker_preview").text());
        a.form_title_font_type = $(this).find("div.font_name").text();
        a.form_title_font_weight = 400;
        a.form_title_font_style = "normal"
    });
    $("#et_ul_typo_form_title_style").delegate("input[type='radio']", "click", function(c) {
        var b = $(this).val().split("-");
        $("#form_title_preview").css("font-weight", b[0]);
        a.form_title_font_weight = parseInt(b[0]);
        if (b[1] == "italic") {
            $("#form_title_preview").css("font-style", "italic");
            a.form_title_font_style = "italic"
        } else {
            $("#form_title_preview").css("font-style", "normal");
            a.form_title_font_style = "normal"
        }
    });
    $("#et_typo_form_title_size_pickerbox").delegate("li", "click", function(b) {
        $("#form_title_preview").css("font-size", $(this).data("fsize"));
        $(this).siblings().removeClass("box_selected");
        $(this).addClass("box_selected");
        a.form_title_font_size = $(this).data("fsize");
        adjust_dropui_positions("fonts")
    });
    $("#dropui-typo-form-desc a.dropui-tab").click(function() {
        $("#dropui-typo-form-title,#dropui-typo-form-desc,#dropui-typo-field-title,#dropui-typo-guidelines,#dropui-typo-section-title,#dropui-typo-section-desc,#dropui-typo-field-text").removeClass("hovered");
        $("#dropui-typo-form-title div.dropui-content,#dropui-typo-form-desc div.dropui-content,#dropui-typo-field-title div.dropui-content,#dropui-typo-guidelines div.dropui-content,#dropui-typo-section-title div.dropui-content,#dropui-typo-section-desc div.dropui-content,#dropui-typo-field-text div.dropui-content").hide();
        $(this).parent().addClass("hovered");
        $(this).next().show();
        if (a.form_desc_font_type == "" || a.form_desc_font_type == "Lucida Grande") {
            $("#et_li_typo_form_desc_font_tab li:eq(0)").addClass("font_selected")
        }
        $("#et_form_desc_font_preview_box").css("font-family", a.form_desc_font_type);
        $("#et_form_desc_font_preview_name").text(a.form_desc_font_type);
        $("#et_typo_form_desc_minicolor_input").miniColors("value", a.form_desc_font_color)
    });
    $("#et_typo_form_desc_minicolor_input").miniColors({
        change: function(c, b) {
            $("#form_desc_preview").css("color", c);
            $("#et_typo_form_desc_minicolor_box").css("background-color", c);
            a.form_desc_font_color = c
        }
    });
    $("#et_li_typo_form_desc_color_tab").delegate("li", "click", function(c) {
        $("#et_li_typo_form_desc_color_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        if ($(this).css("background-color") == "transparent") {
            $("#et_typo_form_desc_minicolor_input").miniColors("value", "");
            $("#form_desc_preview").css("color", "transparent");
            $("#et_typo_form_desc_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
            $("#et_typo_form_desc_minicolor_box").css("background-repeat", "repeat");
            a.form_desc_font_color = "transparent"
        } else {
            $("#et_typo_form_desc_minicolor_box").css("background-image", "");
            var b = rgb2hex($(this).css("background-color"));
            $("#et_typo_form_desc_minicolor_input").miniColors("value", b)
        }
    });
    $("#et_ul_typo_form_desc").delegate("li", "click", function(g) {
        $("#" + $("#et_ul_typo_form_desc li.tab_selected").attr("id") + "_tab").hide();
        $("#et_ul_typo_form_desc li.tab_selected").removeClass("tab_selected");
        $(this).addClass("tab_selected");
        var d = $(this).attr("id");
        if (d == "et_li_typo_form_desc_style") {
            $("#et_typo_form_desc_content").animate({
                width: "295px"
            }, {
                duration: 200,
                queue: false
            });
            $("#dropui-typo-form-desc div.dropui-content").animate({
                width: "350px"
            }, {
                duration: 200,
                queue: false
            });
            var h = a.form_desc_font_type.replace(/ /g, "").toLowerCase();
            var b = $("#header").data("font_" + h);
            if (b == null) {
                b = {
                    "400": "Normal",
                    "400-italic": "Italic",
                    "700": "Bold"
                }
            }
            var c = "";
            $.each(b, function(i, e) {
                c += '<li><input type="radio" name="et_typo_form_desc_style_radio" id="et_typo_form_desc_style_radio' + i + '" value="' + i + '" /> <label for="et_typo_form_desc_style_radio' + i + '">' + e + "</label></li>"
            });
            $("#et_ul_typo_form_desc_style > li").not(".dummy_li").remove();
            $("#et_ul_typo_form_desc_style").append(c);
            if (a.form_desc_font_weight == 0) {
                a.form_desc_font_weight = 400
            }
            var f = a.form_desc_font_weight;
            if (a.form_desc_font_style == "italic") {
                f = f + "-italic"
            }
            $("#et_ul_typo_form_desc_style input[value='" + f + "']").prop("checked", true)
        } else {
            if (d == "et_li_typo_form_desc_size") {
                $("#et_typo_form_desc_content").animate({
                    width: "295px"
                }, {
                    duration: 200,
                    queue: false
                });
                $("#dropui-typo-form-desc div.dropui-content").animate({
                    width: "350px"
                }, {
                    duration: 200,
                    queue: false
                });
                if (a.form_desc_font_size == "") {
                    a.form_desc_font_size = "95%"
                }
                $("#et_typo_form_desc_size_pickerbox li[data-fsize='" + a.form_desc_font_size + "']").addClass("box_selected")
            } else {
                if (d == "et_li_typo_form_desc_color") {
                    $("#et_typo_form_desc_content").animate({
                        width: "305px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-typo-form-desc div.dropui-content").animate({
                        width: "360px"
                    }, {
                        duration: 200,
                        queue: false
                    })
                } else {
                    $("#et_typo_form_desc_content").animate({
                        width: "450px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-typo-form-desc div.dropui-content").animate({
                        width: "507px"
                    }, {
                        duration: 200,
                        queue: false
                    })
                }
            }
        }
        $("#" + d + "_tab").fadeIn()
    });
    $("#et_li_typo_form_desc_font_tab").delegate("li:not(.li_show_more)", "click", function(b) {
        $("#form_desc_preview").css("font-family", $(this).children("div.font_picker_preview").css("font-family"));
        $("#form_desc_preview").css("font-weight", "400");
        $("#form_desc_preview").css("font-style", "normal");
        $(this).siblings().removeClass("font_selected");
        $(this).addClass("font_selected");
        $("#et_form_desc_font_preview_box").css("font-family", $(this).children("div.font_picker_preview").css("font-family"));
        $("#et_form_desc_font_preview_name").text($(this).children("div.font_picker_preview").text());
        a.form_desc_font_type = $(this).find("div.font_name").text();
        a.form_desc_font_weight = 400;
        a.form_desc_font_style = "normal"
    });
    $("#et_ul_typo_form_desc_style").delegate("input[type='radio']", "click", function(c) {
        var b = $(this).val().split("-");
        $("#form_desc_preview").css("font-weight", b[0]);
        a.form_desc_font_weight = parseInt(b[0]);
        if (b[1] == "italic") {
            $("#form_desc_preview").css("font-style", "italic");
            a.form_desc_font_style = "italic"
        } else {
            $("#form_desc_preview").css("font-style", "normal");
            a.form_desc_font_style = "normal"
        }
    });
    $("#et_typo_form_desc_size_pickerbox").delegate("li", "click", function(b) {
        $("#form_desc_preview").css("font-size", $(this).data("fsize"));
        $(this).siblings().removeClass("box_selected");
        $(this).addClass("box_selected");
        a.form_desc_font_size = $(this).data("fsize");
        adjust_dropui_positions("fonts")
    });
    $("#dropui-typo-field-title a.dropui-tab").click(function() {
        $("#dropui-typo-form-title,#dropui-typo-form-desc,#dropui-typo-field-title,#dropui-typo-guidelines,#dropui-typo-section-title,#dropui-typo-section-desc,#dropui-typo-field-text").removeClass("hovered");
        $("#dropui-typo-form-title div.dropui-content,#dropui-typo-form-desc div.dropui-content,#dropui-typo-field-title div.dropui-content,#dropui-typo-guidelines div.dropui-content,#dropui-typo-section-title div.dropui-content,#dropui-typo-section-desc div.dropui-content,#dropui-typo-field-text div.dropui-content").hide();
        $(this).parent().addClass("hovered");
        $(this).next().show();
        if (a.field_title_font_type == "" || a.field_title_font_type == "Lucida Grande") {
            $("#et_li_typo_field_title_font_tab li:eq(0)").addClass("font_selected")
        }
        $("#et_field_title_font_preview_box").css("font-family", a.field_title_font_type);
        $("#et_field_title_font_preview_name").text(a.field_title_font_type);
        $("#et_typo_field_title_minicolor_input").miniColors("value", a.field_title_font_color)
    });
    $("#et_typo_field_title_minicolor_input").miniColors({
        change: function(c, b) {
            $("#form_theme_preview label").css("color", c);
            $("#et_typo_field_title_minicolor_box").css("background-color", c);
            a.field_title_font_color = c
        }
    });
    $("#et_li_typo_field_title_color_tab").delegate("li", "click", function(c) {
        $("#et_li_typo_field_title_color_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        if ($(this).css("background-color") == "transparent") {
            $("#et_typo_field_title_minicolor_input").miniColors("value", "");
            $("#form_theme_preview label").css("color", "transparent");
            $("#et_typo_field_title_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
            $("#et_typo_field_title_minicolor_box").css("background-repeat", "repeat");
            a.field_title_font_color = "transparent"
        } else {
            $("#et_typo_field_title_minicolor_box").css("background-image", "");
            var b = rgb2hex($(this).css("background-color"));
            $("#et_typo_field_title_minicolor_input").miniColors("value", b)
        }
    });
    $("#et_ul_typo_field_title").delegate("li", "click", function(g) {
        $("#" + $("#et_ul_typo_field_title li.tab_selected").attr("id") + "_tab").hide();
        $("#et_ul_typo_field_title li.tab_selected").removeClass("tab_selected");
        $(this).addClass("tab_selected");
        var d = $(this).attr("id");
        if (d == "et_li_typo_field_title_style") {
            $("#et_typo_field_title_content").animate({
                width: "295px"
            }, {
                duration: 200,
                queue: false
            });
            $("#dropui-typo-field-title div.dropui-content").animate({
                width: "350px"
            }, {
                duration: 200,
                queue: false
            });
            var h = a.field_title_font_type.replace(/ /g, "").toLowerCase();
            var b = $("#header").data("font_" + h);
            if (b == null) {
                b = {
                    "400": "Normal",
                    "400-italic": "Italic",
                    "700": "Bold"
                }
            }
            var c = "";
            $.each(b, function(i, e) {
                c += '<li><input type="radio" name="et_typo_field_title_style_radio" id="et_typo_field_title_style_radio' + i + '" value="' + i + '" /> <label for="et_typo_field_title_style_radio' + i + '">' + e + "</label></li>"
            });
            $("#et_ul_typo_field_title_style > li").not(".dummy_li").remove();
            $("#et_ul_typo_field_title_style").append(c);
            if (a.field_title_font_weight == 0) {
                a.field_title_font_weight = 400
            }
            var f = a.field_title_font_weight;
            if (a.field_title_font_style == "italic") {
                f = f + "-italic"
            }
            $("#et_ul_typo_field_title_style input[value='" + f + "']").prop("checked", true)
        } else {
            if (d == "et_li_typo_field_title_size") {
                $("#et_typo_field_title_content").animate({
                    width: "295px"
                }, {
                    duration: 200,
                    queue: false
                });
                $("#dropui-typo-field-title div.dropui-content").animate({
                    width: "350px"
                }, {
                    duration: 200,
                    queue: false
                });
                if (a.field_title_font_size == "") {
                    a.field_title_font_size = "95%"
                }
                $("#et_typo_field_title_size_pickerbox li[data-fsize='" + a.field_title_font_size + "']").addClass("box_selected")
            } else {
                if (d == "et_li_typo_field_title_color") {
                    $("#et_typo_field_title_content").animate({
                        width: "305px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-typo-field-title div.dropui-content").animate({
                        width: "360px"
                    }, {
                        duration: 200,
                        queue: false
                    })
                } else {
                    $("#et_typo_field_title_content").animate({
                        width: "450px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-typo-field-title div.dropui-content").animate({
                        width: "507px"
                    }, {
                        duration: 200,
                        queue: false
                    })
                }
            }
        }
        $("#" + d + "_tab").fadeIn()
    });
    $("#et_li_typo_field_title_font_tab").delegate("li:not(.li_show_more)", "click", function(b) {
        $("#form_theme_preview label").css("font-family", $(this).children("div.font_picker_preview").css("font-family"));
        $("#form_theme_preview label").css("font-weight", "400");
        $("#form_theme_preview label").css("font-style", "normal");
        $(this).siblings().removeClass("font_selected");
        $(this).addClass("font_selected");
        $("#et_field_title_font_preview_box").css("font-family", $(this).children("div.font_picker_preview").css("font-family"));
        $("#et_field_title_font_preview_name").text($(this).children("div.font_picker_preview").text());
        a.field_title_font_type = $(this).find("div.font_name").text();
        a.field_title_font_weight = 400;
        a.field_title_font_style = "normal"
    });
    $("#et_ul_typo_field_title_style").delegate("input[type='radio']", "click", function(c) {
        var b = $(this).val().split("-");
        $("#form_theme_preview label.description").css("font-weight", b[0]);
        a.field_title_font_weight = parseInt(b[0]);
        if (b[1] == "italic") {
            $("#form_theme_preview label").css("font-style", "italic");
            a.field_title_font_style = "italic"
        } else {
            $("#form_theme_preview label").css("font-style", "normal");
            a.field_title_font_style = "normal"
        }
    });
    $("#et_typo_field_title_size_pickerbox").delegate("li", "click", function(b) {
        $("#form_theme_preview label.description").css("font-size", $(this).data("fsize"));
        $(this).siblings().removeClass("box_selected");
        $(this).addClass("box_selected");
        a.field_title_font_size = $(this).data("fsize");
        adjust_dropui_positions("fonts")
    });
    $("#dropui-typo-guidelines a.dropui-tab").click(function() {
        $("#dropui-typo-form-title,#dropui-typo-form-desc,#dropui-typo-field-title,#dropui-typo-guidelines,#dropui-typo-section-title,#dropui-typo-section-desc,#dropui-typo-field-text").removeClass("hovered");
        $("#dropui-typo-form-title div.dropui-content,#dropui-typo-form-desc div.dropui-content,#dropui-typo-field-title div.dropui-content,#dropui-typo-guidelines div.dropui-content,#dropui-typo-section-title div.dropui-content,#dropui-typo-section-desc div.dropui-content,#dropui-typo-field-text div.dropui-content").hide();
        $(this).parent().addClass("hovered");
        $(this).next().show();
        if (a.guidelines_font_type == "" || a.guidelines_font_type == "Lucida Grande") {
            $("#et_li_typo_guidelines_font_tab li:eq(0)").addClass("font_selected")
        }
        $("#et_guidelines_font_preview_box").css("font-family", a.guidelines_font_type);
        $("#et_guidelines_font_preview_name").text(a.guidelines_font_type);
        $("#et_typo_guidelines_minicolor_input").miniColors("value", a.guidelines_font_color)
    });
    $("#et_typo_guidelines_minicolor_input").miniColors({
        change: function(c, b) {
            $("#li_fields p.guidelines small").css("color", c);
            $("#et_typo_guidelines_minicolor_box").css("background-color", c);
            a.guidelines_font_color = c
        }
    });
    $("#et_li_typo_guidelines_color_tab").delegate("li", "click", function(c) {
        $("#et_li_typo_guidelines_color_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        if ($(this).css("background-color") == "transparent") {
            $("#et_typo_guidelines_minicolor_input").miniColors("value", "");
            $("#li_fields p.guidelines small").css("color", "transparent");
            $("#et_typo_guidelines_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
            $("#et_typo_guidelines_minicolor_box").css("background-repeat", "repeat");
            a.guidelines_font_color = "transparent"
        } else {
            $("#et_typo_guidelines_minicolor_box").css("background-image", "");
            var b = rgb2hex($(this).css("background-color"));
            $("#et_typo_guidelines_minicolor_input").miniColors("value", b)
        }
    });
    $("#et_ul_typo_guidelines").delegate("li", "click", function(g) {
        $("#" + $("#et_ul_typo_guidelines li.tab_selected").attr("id") + "_tab").hide();
        $("#et_ul_typo_guidelines li.tab_selected").removeClass("tab_selected");
        $(this).addClass("tab_selected");
        var d = $(this).attr("id");
        if (d == "et_li_typo_guidelines_style") {
            $("#et_typo_guidelines_content").animate({
                width: "295px"
            }, {
                duration: 200,
                queue: false
            });
            $("#dropui-typo-guidelines div.dropui-content").animate({
                width: "350px"
            }, {
                duration: 200,
                queue: false
            });
            var h = a.guidelines_font_type.replace(/ /g, "").toLowerCase();
            var b = $("#header").data("font_" + h);
            if (b == null) {
                b = {
                    "400": "Normal",
                    "400-italic": "Italic",
                    "700": "Bold"
                }
            }
            var c = "";
            $.each(b, function(i, e) {
                c += '<li><input type="radio" name="et_typo_guidelines_style_radio" id="et_typo_guidelines_style_radio' + i + '" value="' + i + '" /> <label for="et_typo_guidelines_style_radio' + i + '">' + e + "</label></li>"
            });
            $("#et_ul_typo_guidelines_style > li").not(".dummy_li").remove();
            $("#et_ul_typo_guidelines_style").append(c);
            if (a.guidelines_font_weight == 0) {
                a.guidelines_font_weight = 400
            }
            var f = a.guidelines_font_weight;
            if (a.guidelines_font_style == "italic") {
                f = f + "-italic"
            }
            $("#et_ul_typo_guidelines_style input[value='" + f + "']").prop("checked", true)
        } else {
            if (d == "et_li_typo_guidelines_size") {
                $("#et_typo_guidelines_content").animate({
                    width: "295px"
                }, {
                    duration: 200,
                    queue: false
                });
                $("#dropui-typo-guidelines div.dropui-content").animate({
                    width: "350px"
                }, {
                    duration: 200,
                    queue: false
                });
                if (a.guidelines_font_size == "") {
                    a.guidelines_font_size = "80%"
                }
                $("#et_typo_guidelines_size_pickerbox li[data-fsize='" + a.guidelines_font_size + "']").addClass("box_selected")
            } else {
                if (d == "et_li_typo_guidelines_color") {
                    $("#et_typo_guidelines_content").animate({
                        width: "305px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-typo-guidelines div.dropui-content").animate({
                        width: "360px"
                    }, {
                        duration: 200,
                        queue: false
                    })
                } else {
                    $("#et_typo_guidelines_content").animate({
                        width: "450px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-typo-guidelines div.dropui-content").animate({
                        width: "507px"
                    }, {
                        duration: 200,
                        queue: false
                    })
                }
            }
        }
        $("#" + d + "_tab").fadeIn()
    });
    $("#et_li_typo_guidelines_font_tab").delegate("li:not(.li_show_more)", "click", function(b) {
        $("#li_fields p.guidelines small").css("font-family", $(this).children("div.font_picker_preview").css("font-family"));
        $("#li_fields p.guidelines small").css("font-weight", "400");
        $("#li_fields p.guidelines small").css("font-style", "normal");
        $(this).siblings().removeClass("font_selected");
        $(this).addClass("font_selected");
        $("#et_guidelines_font_preview_box").css("font-family", $(this).children("div.font_picker_preview").css("font-family"));
        $("#et_guidelines_font_preview_name").text($(this).children("div.font_picker_preview").text());
        a.guidelines_font_type = $(this).find("div.font_name").text();
        a.guidelines_font_weight = 400;
        a.guidelines_font_style = "normal"
    });
    $("#et_ul_typo_guidelines_style").delegate("input[type='radio']", "click", function(c) {
        var b = $(this).val().split("-");
        $("#li_fields p.guidelines small").css("font-weight", b[0]);
        a.guidelines_font_weight = parseInt(b[0]);
        if (b[1] == "italic") {
            $("#li_fields p.guidelines small").css("font-style", "italic");
            a.guidelines_font_style = "italic"
        } else {
            $("#li_fields p.guidelines small").css("font-style", "normal");
            a.guidelines_font_style = "normal"
        }
    });
    $("#et_typo_guidelines_size_pickerbox").delegate("li", "click", function(b) {
        $("#li_fields p.guidelines small").css("font-size", $(this).data("fsize"));
        $(this).siblings().removeClass("box_selected");
        $(this).addClass("box_selected");
        a.guidelines_font_size = $(this).data("fsize");
        adjust_dropui_positions("fonts")
    });
    $("#dropui-typo-section-title a.dropui-tab").click(function() {
        $("#dropui-typo-form-title,#dropui-typo-form-desc,#dropui-typo-field-title,#dropui-typo-guidelines,#dropui-typo-section-title,#dropui-typo-section-desc,#dropui-typo-field-text").removeClass("hovered");
        $("#dropui-typo-form-title div.dropui-content,#dropui-typo-form-desc div.dropui-content,#dropui-typo-field-title div.dropui-content,#dropui-typo-guidelines div.dropui-content,#dropui-typo-section-title div.dropui-content,#dropui-typo-section-desc div.dropui-content,#dropui-typo-field-text div.dropui-content").hide();
        $(this).parent().addClass("hovered");
        $(this).next().show();
        if (a.section_title_font_type == "" || a.section_title_font_type == "Lucida Grande") {
            $("#et_li_typo_section_title_font_tab li:eq(0)").addClass("font_selected")
        }
        $("#et_section_title_font_preview_box").css("font-family", a.section_title_font_type);
        $("#et_section_title_font_preview_name").text(a.section_title_font_type);
        $("#et_typo_section_title_minicolor_input").miniColors("value", a.section_title_font_color)
    });
    $("#et_typo_section_title_minicolor_input").miniColors({
        change: function(c, b) {
            $("#section_title_preview").css("color", c);
            $("#et_typo_section_title_minicolor_box").css("background-color", c);
            a.section_title_font_color = c
        }
    });
    $("#et_li_typo_section_title_color_tab").delegate("li", "click", function(c) {
        $("#et_li_typo_section_title_color_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        if ($(this).css("background-color") == "transparent") {
            $("#et_typo_section_title_minicolor_input").miniColors("value", "");
            $("#section_title_preview").css("color", "transparent");
            $("#et_typo_section_title_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
            $("#et_typo_section_title_minicolor_box").css("background-repeat", "repeat");
            a.section_title_font_color = "transparent"
        } else {
            $("#et_typo_section_title_minicolor_box").css("background-image", "");
            var b = rgb2hex($(this).css("background-color"));
            $("#et_typo_section_title_minicolor_input").miniColors("value", b)
        }
    });
    $("#et_ul_typo_section_title").delegate("li", "click", function(g) {
        $("#" + $("#et_ul_typo_section_title li.tab_selected").attr("id") + "_tab").hide();
        $("#et_ul_typo_section_title li.tab_selected").removeClass("tab_selected");
        $(this).addClass("tab_selected");
        var d = $(this).attr("id");
        if (d == "et_li_typo_section_title_style") {
            $("#et_typo_section_title_content").animate({
                width: "295px"
            }, {
                duration: 200,
                queue: false
            });
            $("#dropui-typo-section-title div.dropui-content").animate({
                width: "350px"
            }, {
                duration: 200,
                queue: false
            });
            var h = a.section_title_font_type.replace(/ /g, "").toLowerCase();
            var b = $("#header").data("font_" + h);
            if (b == null) {
                b = {
                    "400": "Normal",
                    "400-italic": "Italic",
                    "700": "Bold"
                }
            }
            var c = "";
            $.each(b, function(i, e) {
                c += '<li><input type="radio" name="et_typo_section_title_style_radio" id="et_typo_section_title_style_radio' + i + '" value="' + i + '" /> <label for="et_typo_section_title_style_radio' + i + '">' + e + "</label></li>"
            });
            $("#et_ul_typo_section_title_style > li").not(".dummy_li").remove();
            $("#et_ul_typo_section_title_style").append(c);
            if (a.section_title_font_weight == 0) {
                a.section_title_font_weight = 400
            }
            var f = a.section_title_font_weight;
            if (a.section_title_font_style == "italic") {
                f = f + "-italic"
            }
            $("#et_ul_typo_section_title_style input[value='" + f + "']").prop("checked", true)
        } else {
            if (d == "et_li_typo_section_title_size") {
                $("#et_typo_section_title_content").animate({
                    width: "295px"
                }, {
                    duration: 200,
                    queue: false
                });
                $("#dropui-typo-section-title div.dropui-content").animate({
                    width: "350px"
                }, {
                    duration: 200,
                    queue: false
                });
                if (a.section_title_font_size == "") {
                    a.section_title_font_size = "110%"
                }
                $("#et_typo_section_title_size_pickerbox li[data-fsize='" + a.section_title_font_size + "']").addClass("box_selected")
            } else {
                if (d == "et_li_typo_section_title_color") {
                    $("#et_typo_section_title_content").animate({
                        width: "305px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-typo-section-title div.dropui-content").animate({
                        width: "360px"
                    }, {
                        duration: 200,
                        queue: false
                    })
                } else {
                    $("#et_typo_section_title_content").animate({
                        width: "450px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-typo-section-title div.dropui-content").animate({
                        width: "507px"
                    }, {
                        duration: 200,
                        queue: false
                    })
                }
            }
        }
        $("#" + d + "_tab").fadeIn()
    });
    $("#et_li_typo_section_title_font_tab").delegate("li:not(.li_show_more)", "click", function(b) {
        $("#section_title_preview").css("font-family", $(this).children("div.font_picker_preview").css("font-family"));
        $("#section_title_preview").css("font-weight", "400");
        $("#section_title_preview").css("font-style", "normal");
        $(this).siblings().removeClass("font_selected");
        $(this).addClass("font_selected");
        $("#et_section_title_font_preview_box").css("font-family", $(this).children("div.font_picker_preview").css("font-family"));
        $("#et_section_title_font_preview_name").text($(this).children("div.font_picker_preview").text());
        a.section_title_font_type = $(this).find("div.font_name").text();
        a.section_title_font_weight = 400;
        a.section_title_font_style = "normal"
    });
    $("#et_ul_typo_section_title_style").delegate("input[type='radio']", "click", function(c) {
        var b = $(this).val().split("-");
        $("#section_title_preview").css("font-weight", b[0]);
        a.section_title_font_weight = parseInt(b[0]);
        if (b[1] == "italic") {
            $("#section_title_preview").css("font-style", "italic");
            a.section_title_font_style = "italic"
        } else {
            $("#section_title_preview").css("font-style", "normal");
            a.section_title_font_style = "normal"
        }
    });
    $("#et_typo_section_title_size_pickerbox").delegate("li", "click", function(b) {
        $("#section_title_preview").css("font-size", $(this).data("fsize"));
        $(this).siblings().removeClass("box_selected");
        $(this).addClass("box_selected");
        a.section_title_font_size = $(this).data("fsize");
        adjust_dropui_positions("fonts")
    });
    $("#dropui-typo-section-desc a.dropui-tab").click(function() {
        $("#dropui-typo-form-title,#dropui-typo-form-desc,#dropui-typo-field-title,#dropui-typo-guidelines,#dropui-typo-section-title,#dropui-typo-section-desc,#dropui-typo-field-text").removeClass("hovered");
        $("#dropui-typo-form-title div.dropui-content,#dropui-typo-form-desc div.dropui-content,#dropui-typo-field-title div.dropui-content,#dropui-typo-guidelines div.dropui-content,#dropui-typo-section-title div.dropui-content,#dropui-typo-section-desc div.dropui-content,#dropui-typo-field-text div.dropui-content").hide();
        $("#container").css("margin-bottom", "250px");
        $(this).parent().addClass("hovered");
        $(this).next().show();
        if (a.section_desc_font_type == "" || a.section_desc_font_type == "Lucida Grande") {
            $("#et_li_typo_section_desc_font_tab li:eq(0)").addClass("font_selected")
        }
        $("#et_section_desc_font_preview_box").css("font-family", a.section_desc_font_type);
        $("#et_section_desc_font_preview_name").text(a.section_desc_font_type);
        $("#et_typo_section_desc_minicolor_input").miniColors("value", a.section_desc_font_color)
    });
    $("#et_typo_section_desc_minicolor_input").miniColors({
        change: function(c, b) {
            $("#section_desc_preview").css("color", c);
            $("#et_typo_section_desc_minicolor_box").css("background-color", c);
            a.section_desc_font_color = c
        }
    });
    $("#et_li_typo_section_desc_color_tab").delegate("li", "click", function(c) {
        $("#et_li_typo_section_desc_color_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        if ($(this).css("background-color") == "transparent") {
            $("#et_typo_section_desc_minicolor_input").miniColors("value", "");
            $("#section_desc_preview").css("color", "transparent");
            $("#et_typo_section_desc_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
            $("#et_typo_section_desc_minicolor_box").css("background-repeat", "repeat");
            a.section_desc_font_color = "transparent"
        } else {
            $("#et_typo_section_desc_minicolor_box").css("background-image", "");
            var b = rgb2hex($(this).css("background-color"));
            $("#et_typo_section_desc_minicolor_input").miniColors("value", b)
        }
    });
    $("#et_ul_typo_section_desc").delegate("li", "click", function(g) {
        $("#" + $("#et_ul_typo_section_desc li.tab_selected").attr("id") + "_tab").hide();
        $("#et_ul_typo_section_desc li.tab_selected").removeClass("tab_selected");
        $(this).addClass("tab_selected");
        var d = $(this).attr("id");
        if (d == "et_li_typo_section_desc_style") {
            $("#et_typo_section_desc_content").animate({
                width: "295px"
            }, {
                duration: 200,
                queue: false
            });
            $("#dropui-typo-section-desc div.dropui-content").animate({
                width: "350px"
            }, {
                duration: 200,
                queue: false
            });
            var h = a.section_desc_font_type.replace(/ /g, "").toLowerCase();
            var b = $("#header").data("font_" + h);
            if (b == null) {
                b = {
                    "400": "Normal",
                    "400-italic": "Italic",
                    "700": "Bold"
                }
            }
            var c = "";
            $.each(b, function(i, e) {
                c += '<li><input type="radio" name="et_typo_section_desc_style_radio" id="et_typo_section_desc_style_radio' + i + '" value="' + i + '" /> <label for="et_typo_section_desc_style_radio' + i + '">' + e + "</label></li>"
            });
            $("#et_ul_typo_section_desc_style > li").not(".dummy_li").remove();
            $("#et_ul_typo_section_desc_style").append(c);
            if (a.section_desc_font_weight == 0) {
                a.section_desc_font_weight = 400
            }
            var f = a.section_desc_font_weight;
            if (a.section_desc_font_style == "italic") {
                f = f + "-italic"
            }
            $("#et_ul_typo_section_desc_style input[value='" + f + "']").prop("checked", true)
        } else {
            if (d == "et_li_typo_section_desc_size") {
                $("#et_typo_section_desc_content").animate({
                    width: "295px"
                }, {
                    duration: 200,
                    queue: false
                });
                $("#dropui-typo-section-desc div.dropui-content").animate({
                    width: "350px"
                }, {
                    duration: 200,
                    queue: false
                });
                if (a.section_desc_font_size == "") {
                    a.section_desc_font_size = "85%"
                }
                $("#et_typo_section_desc_size_pickerbox li[data-fsize='" + a.section_desc_font_size + "']").addClass("box_selected")
            } else {
                if (d == "et_li_typo_section_desc_color") {
                    $("#et_typo_section_desc_content").animate({
                        width: "305px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-typo-section-desc div.dropui-content").animate({
                        width: "360px"
                    }, {
                        duration: 200,
                        queue: false
                    })
                } else {
                    $("#et_typo_section_desc_content").animate({
                        width: "450px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-typo-section-desc div.dropui-content").animate({
                        width: "507px"
                    }, {
                        duration: 200,
                        queue: false
                    })
                }
            }
        }
        $("#" + d + "_tab").fadeIn()
    });
    $("#et_li_typo_section_desc_font_tab").delegate("li:not(.li_show_more)", "click", function(b) {
        $("#section_desc_preview").css("font-family", $(this).children("div.font_picker_preview").css("font-family"));
        $("#section_desc_preview").css("font-weight", "400");
        $("#section_desc_preview").css("font-style", "normal");
        $(this).siblings().removeClass("font_selected");
        $(this).addClass("font_selected");
        $("#et_section_desc_font_preview_box").css("font-family", $(this).children("div.font_picker_preview").css("font-family"));
        $("#et_section_desc_font_preview_name").text($(this).children("div.font_picker_preview").text());
        a.section_desc_font_type = $(this).find("div.font_name").text();
        a.section_desc_font_weight = 400;
        a.section_desc_font_style = "normal"
    });
    $("#et_ul_typo_section_desc_style").delegate("input[type='radio']", "click", function(c) {
        var b = $(this).val().split("-");
        $("#section_desc_preview").css("font-weight", b[0]);
        a.section_desc_font_weight = parseInt(b[0]);
        if (b[1] == "italic") {
            $("#section_desc_preview").css("font-style", "italic");
            a.section_desc_font_style = "italic"
        } else {
            $("#section_desc_preview").css("font-style", "normal");
            a.section_desc_font_style = "normal"
        }
    });
    $("#et_typo_section_desc_size_pickerbox").delegate("li", "click", function(b) {
        $("#section_desc_preview").css("font-size", $(this).data("fsize"));
        $(this).siblings().removeClass("box_selected");
        $(this).addClass("box_selected");
        a.section_desc_font_size = $(this).data("fsize");
        adjust_dropui_positions("fonts")
    });
    $("#dropui-typo-field-text a.dropui-tab").click(function() {
        $("#dropui-typo-form-title,#dropui-typo-form-desc,#dropui-typo-field-title,#dropui-typo-guidelines,#dropui-typo-section-title,#dropui-typo-section-desc,#dropui-typo-field-text").removeClass("hovered");
        $("#dropui-typo-form-title div.dropui-content,#dropui-typo-form-desc div.dropui-content,#dropui-typo-field-title div.dropui-content,#dropui-typo-guidelines div.dropui-content,#dropui-typo-section-title div.dropui-content,#dropui-typo-section-desc div.dropui-content,#dropui-typo-field-text div.dropui-content").hide();
        $("#container").css("margin-bottom", "250px");
        $(this).parent().addClass("hovered");
        $(this).next().show();
        if (a.field_text_font_type == "" || a.field_text_font_type == "Lucida Grande") {
            $("#et_li_typo_field_text_font_tab li:eq(0)").addClass("font_selected")
        }
        $("#et_field_text_font_preview_box").css("font-family", a.field_text_font_type);
        $("#et_field_text_font_preview_name").text(a.field_text_font_type);
        $("#et_typo_field_text_minicolor_input").miniColors("value", a.field_text_font_color)
    });
    $("#et_typo_field_text_minicolor_input").miniColors({
        change: function(c, b) {
            $("#form_theme_preview :input").not(".submit_button").css("color", c);
            $("#et_typo_field_text_minicolor_box").css("background-color", c);
            a.field_text_font_color = c
        }
    });
    $("#et_li_typo_field_text_color_tab").delegate("li", "click", function(c) {
        $("#et_li_typo_field_text_color_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        if ($(this).css("background-color") == "transparent") {
            $("#et_typo_field_text_minicolor_input").miniColors("value", "");
            $("#form_theme_preview :input").not(".submit_button").css("color", "transparent");
            $("#et_typo_field_text_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
            $("#et_typo_field_text_minicolor_box").css("background-repeat", "repeat");
            a.field_text_font_color = "transparent"
        } else {
            $("#et_typo_field_text_minicolor_box").css("background-image", "");
            var b = rgb2hex($(this).css("background-color"));
            $("#et_typo_field_text_minicolor_input").miniColors("value", b)
        }
    });
    $("#et_ul_typo_field_text").delegate("li", "click", function(g) {
        $("#" + $("#et_ul_typo_field_text li.tab_selected").attr("id") + "_tab").hide();
        $("#et_ul_typo_field_text li.tab_selected").removeClass("tab_selected");
        $(this).addClass("tab_selected");
        var d = $(this).attr("id");
        if (d == "et_li_typo_field_text_style") {
            $("#et_typo_field_text_content").animate({
                width: "295px"
            }, {
                duration: 200,
                queue: false
            });
            $("#dropui-typo-field-text div.dropui-content").animate({
                width: "350px"
            }, {
                duration: 200,
                queue: false
            });
            var h = a.field_text_font_type.replace(/ /g, "").toLowerCase();
            var b = $("#header").data("font_" + h);
            if (b == null) {
                b = {
                    "400": "Normal",
                    "400-italic": "Italic",
                    "700": "Bold"
                }
            }
            var c = "";
            $.each(b, function(i, e) {
                c += '<li><input type="radio" name="et_typo_field_text_style_radio" id="et_typo_field_text_style_radio' + i + '" value="' + i + '" /> <label for="et_typo_field_text_style_radio' + i + '">' + e + "</label></li>"
            });
            $("#et_ul_typo_field_text_style > li").not(".dummy_li").remove();
            $("#et_ul_typo_field_text_style").append(c);
            if (a.field_text_font_weight == 0) {
                a.field_text_font_weight = 400
            }
            var f = a.field_text_font_weight;
            if (a.field_text_font_style == "italic") {
                f = f + "-italic"
            }
            $("#et_ul_typo_field_text_style input[value='" + f + "']").prop("checked", true)
        } else {
            if (d == "et_li_typo_field_text_size") {
                $("#et_typo_field_text_content").animate({
                    width: "295px"
                }, {
                    duration: 200,
                    queue: false
                });
                $("#dropui-typo-field-text div.dropui-content").animate({
                    width: "350px"
                }, {
                    duration: 200,
                    queue: false
                });
                if (a.field_text_font_size == "") {
                    a.field_text_font_size = "100%"
                }
                $("#et_typo_field_text_size_pickerbox li[data-fsize='" + a.field_text_font_size + "']").addClass("box_selected")
            } else {
                if (d == "et_li_typo_field_text_color") {
                    $("#et_typo_field_text_content").animate({
                        width: "305px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-typo-field-text div.dropui-content").animate({
                        width: "360px"
                    }, {
                        duration: 200,
                        queue: false
                    })
                } else {
                    $("#et_typo_field_text_content").animate({
                        width: "450px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-typo-field-text div.dropui-content").animate({
                        width: "507px"
                    }, {
                        duration: 200,
                        queue: false
                    })
                }
            }
        }
        $("#" + d + "_tab").fadeIn()
    });
    $("#et_li_typo_field_text_font_tab").delegate("li:not(.li_show_more)", "click", function(b) {
        $("#form_theme_preview :input").not(".submit_button").css("font-family", $(this).children("div.font_picker_preview").css("font-family"));
        $("#form_theme_preview :input").not(".submit_button").css("font-weight", "400");
        $("#form_theme_preview :input").not(".submit_button").css("font-style", "normal");
        $(this).siblings().removeClass("font_selected");
        $(this).addClass("font_selected");
        $("#et_field_text_font_preview_box").css("font-family", $(this).children("div.font_picker_preview").css("font-family"));
        $("#et_field_text_font_preview_name").text($(this).children("div.font_picker_preview").text());
        a.field_text_font_type = $(this).find("div.font_name").text();
        a.field_text_font_weight = 400;
        a.field_text_font_style = "normal"
    });
    $("#et_ul_typo_field_text_style").delegate("input[type='radio']", "click", function(c) {
        var b = $(this).val().split("-");
        $("#form_theme_preview :input").not(".submit_button").css("font-weight", b[0]);
        a.field_text_font_weight = parseInt(b[0]);
        if (b[1] == "italic") {
            $("#form_theme_preview :input").not(".submit_button").css("font-style", "italic");
            a.field_text_font_style = "italic"
        } else {
            $("#form_theme_preview :input").not(".submit_button").css("font-style", "normal");
            a.field_text_font_style = "normal"
        }
    });
    $("#et_typo_field_text_size_pickerbox").delegate("li", "click", function(b) {
        $("#form_theme_preview :input").not(".submit_button").css("font-size", $(this).data("fsize"));
        $(this).siblings().removeClass("box_selected");
        $(this).addClass("box_selected");
        a.field_text_font_size = $(this).data("fsize");
        adjust_dropui_positions("fonts")
    });
    $("#dropui-border-form a.dropui-tab").click(function() {
        $("#dropui-border-form,#dropui-border-guidelines,#dropui-border-section").removeClass("hovered");
        $("#dropui-border-form div.dropui-content,#dropui-border-guidelines div.dropui-content,#dropui-border-section div.dropui-content").hide();
        $(this).parent().addClass("hovered");
        $(this).next().show();
        $("#et_ul_border_form_thickness input[value='" + a.border_form_width + "']").prop("checked", true)
    });
    $("#et_ul_border_form").delegate("li", "click", function(c) {
        $("#" + $("#et_ul_border_form li.tab_selected").attr("id") + "_tab").hide();
        $("#et_ul_border_form li.tab_selected").removeClass("tab_selected");
        $(this).addClass("tab_selected");
        var b = $(this).attr("id");
        if (b == "et_li_border_form_thickness") {
            $("#et_border_form_content").animate({
                width: "215px"
            }, {
                duration: 200,
                queue: false
            });
            $("#dropui-border-form div.dropui-content").animate({
                width: "270px"
            }, {
                duration: 200,
                queue: false
            });
            $("#et_ul_border_form_thickness input[value='" + a.border_form_width + "']").prop("checked", true)
        } else {
            if (b == "et_li_border_form_style") {
                $("#et_border_form_content").animate({
                    width: "215px"
                }, {
                    duration: 200,
                    queue: false
                });
                $("#dropui-border-form div.dropui-content").animate({
                    width: "270px"
                }, {
                    duration: 200,
                    queue: false
                });
                $("#et_ul_border_form_style input[value='" + a.border_form_style + "']").prop("checked", true)
            } else {
                if (b == "et_li_border_form_color") {
                    $("#et_border_form_content").animate({
                        width: "305px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-border-form div.dropui-content").animate({
                        width: "360px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#et_border_form_minicolor_input").miniColors("value", a.border_form_color)
                }
            }
        }
        $("#" + b + "_tab").fadeIn()
    });
    $("#et_ul_border_form_thickness").delegate("input[type='radio']", "click", function(b) {
        $("#form_container").css("border-width", $(this).val() + "px");
        a.border_form_width = parseInt($(this).val())
    });
    $("#et_ul_border_form_style").delegate("input[type='radio']", "click", function(b) {
        $("#form_container").css("border-style", $(this).val());
        a.border_form_style = $(this).val()
    });
    $("#et_border_form_minicolor_input").miniColors({
        change: function(c, b) {
            $("#form_container").css("border-color", c);
            $("#et_border_form_minicolor_box").css("background-color", c);
            a.border_form_color = c
        }
    });
    $("#et_li_border_form_color_tab").delegate("li", "click", function(c) {
        $("#et_li_border_form_color_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        if ($(this).css("background-color") == "transparent") {
            $("#et_border_form_minicolor_input").miniColors("value", "");
            $("#form_container").css("border-color", "transparent");
            $("#et_border_form_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
            $("#et_border_form_minicolor_box").css("background-repeat", "repeat");
            a.border_form_color = "transparent"
        } else {
            $("#et_border_form_minicolor_box").css("background-image", "");
            var b = rgb2hex($(this).css("background-color"));
            $("#et_border_form_minicolor_input").miniColors("value", b)
        }
    });
    $("#dropui-border-guidelines a.dropui-tab").click(function() {
        $("#dropui-border-form,#dropui-border-guidelines,#dropui-border-section").removeClass("hovered");
        $("#dropui-border-form div.dropui-content,#dropui-border-guidelines div.dropui-content,#dropui-border-section div.dropui-content").hide();
        $(this).parent().addClass("hovered");
        $(this).next().show();
        $("#et_ul_border_guidelines_thickness input[value='" + a.border_guidelines_width + "']").prop("checked", true)
    });
    $("#et_ul_border_guidelines").delegate("li", "click", function(c) {
        $("#" + $("#et_ul_border_guidelines li.tab_selected").attr("id") + "_tab").hide();
        $("#et_ul_border_guidelines li.tab_selected").removeClass("tab_selected");
        $(this).addClass("tab_selected");
        var b = $(this).attr("id");
        if (b == "et_li_border_guidelines_thickness") {
            $("#et_border_guidelines_content").animate({
                width: "215px"
            }, {
                duration: 200,
                queue: false
            });
            $("#dropui-border-guidelines div.dropui-content").animate({
                width: "270px"
            }, {
                duration: 200,
                queue: false
            });
            $("#et_ul_border_guidelines_thickness input[value='" + a.border_guidelines_width + "']").prop("checked", true)
        } else {
            if (b == "et_li_border_guidelines_style") {
                $("#et_border_guidelines_content").animate({
                    width: "215px"
                }, {
                    duration: 200,
                    queue: false
                });
                $("#dropui-border-guidelines div.dropui-content").animate({
                    width: "270px"
                }, {
                    duration: 200,
                    queue: false
                });
                $("#et_ul_border_guidelines_style input[value='" + a.border_guidelines_style + "']").prop("checked", true)
            } else {
                if (b == "et_li_border_guidelines_color") {
                    $("#et_border_guidelines_content").animate({
                        width: "305px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-border-guidelines div.dropui-content").animate({
                        width: "360px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#et_border_guidelines_minicolor_input").miniColors("value", a.border_guidelines_color)
                }
            }
        }
        $("#" + b + "_tab").fadeIn()
    });
    $("#et_ul_border_guidelines_thickness").delegate("input[type='radio']", "click", function(b) {
        $("#guide_2").css("border-width", $(this).val() + "px");
        a.border_guidelines_width = parseInt($(this).val())
    });
    $("#et_ul_border_guidelines_style").delegate("input[type='radio']", "click", function(b) {
        $("#guide_2").css("border-style", $(this).val());
        a.border_guidelines_style = $(this).val()
    });
    $("#et_border_guidelines_minicolor_input").miniColors({
        change: function(c, b) {
            $("#guide_2").css("border-color", c);
            $("#et_border_guidelines_minicolor_box").css("background-color", c);
            a.border_guidelines_color = c
        }
    });
    $("#et_li_border_guidelines_color_tab").delegate("li", "click", function(c) {
        $("#et_li_border_guidelines_color_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        if ($(this).css("background-color") == "transparent") {
            $("#et_border_guidelines_minicolor_input").miniColors("value", "");
            $("#guide_2").css("border-color", "transparent");
            $("#et_border_guidelines_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
            $("#et_border_guidelines_minicolor_box").css("background-repeat", "repeat");
            a.border_guidelines_color = "transparent"
        } else {
            $("#et_border_guidelines_minicolor_box").css("background-image", "");
            var b = rgb2hex($(this).css("background-color"));
            $("#et_border_guidelines_minicolor_input").miniColors("value", b)
        }
    });
    $("#dropui-border-section a.dropui-tab").click(function() {
        $("#dropui-border-form,#dropui-border-guidelines,#dropui-border-section").removeClass("hovered");
        $("#dropui-border-form div.dropui-content,#dropui-border-guidelines div.dropui-content,#dropui-border-section div.dropui-content").hide();
        $(this).parent().addClass("hovered");
        $(this).next().show();
        $("#et_ul_border_section_thickness input[value='" + a.border_section_width + "']").prop("checked", true)
    });
    $("#et_ul_border_section").delegate("li", "click", function(c) {
        $("#" + $("#et_ul_border_section li.tab_selected").attr("id") + "_tab").hide();
        $("#et_ul_border_section li.tab_selected").removeClass("tab_selected");
        $(this).addClass("tab_selected");
        var b = $(this).attr("id");
        if (b == "et_li_border_section_thickness") {
            $("#et_border_section_content").animate({
                width: "215px"
            }, {
                duration: 200,
                queue: false
            });
            $("#dropui-border-section div.dropui-content").animate({
                width: "270px"
            }, {
                duration: 200,
                queue: false
            });
            $("#et_ul_border_section_thickness input[value='" + a.border_section_width + "']").prop("checked", true)
        } else {
            if (b == "et_li_border_section_style") {
                $("#et_border_section_content").animate({
                    width: "215px"
                }, {
                    duration: 200,
                    queue: false
                });
                $("#dropui-border-section div.dropui-content").animate({
                    width: "270px"
                }, {
                    duration: 200,
                    queue: false
                });
                $("#et_ul_border_section_style input[value='" + a.border_section_style + "']").prop("checked", true)
            } else {
                if (b == "et_li_border_section_color") {
                    $("#et_border_section_content").animate({
                        width: "305px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-border-section div.dropui-content").animate({
                        width: "360px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#et_border_section_minicolor_input").miniColors("value", a.border_section_color)
                }
            }
        }
        $("#" + b + "_tab").fadeIn()
    });
    $("#et_ul_border_section_thickness").delegate("input[type='radio']", "click", function(b) {
        $("#li_4").css("border-top-width", $(this).val() + "px");
        a.border_section_width = parseInt($(this).val())
    });
    $("#et_ul_border_section_style").delegate("input[type='radio']", "click", function(b) {
        $("#li_4").css("border-top-style", $(this).val());
        a.border_section_style = $(this).val()
    });
    $("#et_border_section_minicolor_input").miniColors({
        change: function(c, b) {
            $("#li_4").css("border-top-color", c);
            $("#et_border_section_minicolor_box").css("background-color", c);
            a.border_section_color = c
        }
    });
    $("#et_li_border_section_color_tab").delegate("li", "click", function(c) {
        $("#et_li_border_section_color_tab li.picker_selected").removeClass("picker_selected");
        $(this).addClass("picker_selected");
        if ($(this).css("background-color") == "transparent") {
            $("#et_border_section_minicolor_input").miniColors("value", "");
            $("#li_4").css("border-top-color", "transparent");
            $("#et_border_section_minicolor_box").css("background-image", "url('images/icons/transparent.png')");
            $("#et_border_section_minicolor_box").css("background-repeat", "repeat");
            a.border_section_color = "transparent"
        } else {
            $("#et_border_section_minicolor_box").css("background-image", "");
            var b = rgb2hex($(this).css("background-color"));
            $("#et_border_section_minicolor_input").miniColors("value", b)
        }
    });
    $("#dropui-form-shadow a.dropui-tab").click(function() {
        $(this).parent().addClass("hovered");
        $(this).next().show();
        $("#container").css("margin-bottom", "250px");
        $("#et_ul_form_shadow_style input[value='" + a.form_shadow_style + "']").prop("checked", true)
    });
    $("#et_ul_form_shadow").delegate("li", "click", function(c) {
        $("#" + $("#et_ul_form_shadow li.tab_selected").attr("id") + "_tab").hide();
        $("#et_ul_form_shadow li.tab_selected").removeClass("tab_selected");
        $(this).addClass("tab_selected");
        var b = $(this).attr("id");
        if (b == "et_li_form_shadow_style") {
            $("#et_form_shadow_content").animate({
                width: "305px"
            }, {
                duration: 200,
                queue: false
            });
            $("#dropui-form-shadow div.dropui-content").animate({
                width: "360px"
            }, {
                duration: 200,
                queue: false
            });
            $("#et_ul_form_shadow_style input[value='" + a.form_shadow_style + "']").prop("checked", true)
        } else {
            if (b == "et_li_form_shadow_size") {
                $("#et_form_shadow_content").animate({
                    width: "215px"
                }, {
                    duration: 200,
                    queue: false
                });
                $("#dropui-form-shadow div.dropui-content").animate({
                    width: "270px"
                }, {
                    duration: 200,
                    queue: false
                });
                $("#et_ul_form_shadow_size input[value='" + a.form_shadow_size + "']").prop("checked", true)
            } else {
                if (b == "et_li_form_shadow_brightness") {
                    $("#et_form_shadow_content").animate({
                        width: "215px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#dropui-form-shadow div.dropui-content").animate({
                        width: "270px"
                    }, {
                        duration: 200,
                        queue: false
                    });
                    $("#et_ul_form_shadow_brightness input[value='" + a.form_shadow_brightness + "']").prop("checked", true)
                }
            }
        }
        $("#" + b + "_tab").fadeIn()
    });
    $("#et_ul_form_shadow_style").delegate("input[type='radio']", "click", function(f) {
        var c = $(this).val();
        $("#form_container").removeClass();
        if (c != "disabled") {
            var b = c.match(/[A-Z]/g).join("").slice(0, -1);
            var g = b + ucfirst(a.form_shadow_size);
            var d = b + ucfirst(a.form_shadow_brightness);
            $("#form_container").addClass(c + " " + g + " " + d)
        }
        a.form_shadow_style = c
    });
    $("#et_ul_form_shadow_size").delegate("input[type='radio']", "click", function(c) {
        var b = a.form_shadow_style.match(/[A-Z]/g).join("").slice(0, -1);
        $("#form_container").removeClass(b + "Small " + b + "Medium " + b + "Large");
        $("#form_container").addClass(b + ucfirst($(this).val()));
        a.form_shadow_size = $(this).val()
    });
    $("#et_ul_form_shadow_brightness").delegate("input[type='radio']", "click", function(c) {
        var b = a.form_shadow_style.match(/[A-Z]/g).join("").slice(0, -1);
        $("#form_container").removeClass(b + "Light " + b + "Normal " + b + "Dark");
        $("#form_container").addClass(b + ucfirst($(this).val()));
        a.form_shadow_brightness = $(this).val()
    });
    $("#dropui-form-button a.dropui-tab").click(function() {
        $(this).parent().addClass("hovered");
        $(this).next().show();
        $("#et_ul_form_button li.prop_selected").removeClass("prop_selected");
        $("#et_form_button_content > div").hide();
        if (a.form_button_image == "") {
            a.form_button_image = "http://"
        }
        $("#et_form_button_text_input").val(a.form_button_text);
        $("#et_form_button_image_input").val(a.form_button_image);
        $("#submit_form").val(a.form_button_text);
        $("#submit_form_image").attr("src", a.form_button_image);
        if (a.form_button_type == "text") {
            $("#et_form_button_text").parent().addClass("prop_selected");
            $("#et_form_button_text").prop("checked", true);
            $("#et_form_button_text_tab").show()
        } else {
            if (a.form_button_type == "image") {
                $("#et_form_button_image").parent().addClass("prop_selected");
                $("#et_form_button_image").prop("checked", true);
                $("#et_form_button_image_tab").show()
            }
        }
    });
    $("#et_ul_form_button input[type=radio]").click(function() {
        $("#" + $("#et_ul_form_button li.prop_selected input").attr("id") + "_tab").hide();
        $("#et_ul_form_button li.prop_selected").removeClass("prop_selected");
        $(this).parent().addClass("prop_selected");
        $("#" + $(this).attr("id") + "_tab").fadeIn();
        var b = $(this).attr("id");
        if (b == "et_form_button_text") {
            a.form_button_type = "text";
            $("#submit_form_image").hide();
            $("#submit_form").show()
        } else {
            if (b == "et_form_button_image") {
                a.form_button_type = "image";
                $("#submit_form").hide();
                $("#submit_form_image").show()
            }
        }
    });
    $("#et_form_button_text_input").bind("keyup mouseout change", function() {
        $("#submit_form").val($(this).val());
        a.form_button_text = $(this).val()
    });
    $("#et_form_button_image_input").bind("keyup mouseout change", function() {
        $("#submit_form_image").attr("src", $(this).val());
        a.form_button_image = $(this).val()
    });
    $("#dialog-name-theme").dialog({
        modal: true,
        autoOpen: false,
        closeOnEscape: false,
        width: 400,
        draggable: false,
        resizable: false,
        buttons: [{
            text: "Save Changes",
            id: "dialog-name-theme-btn-save-changes",
            "class": "bb_button bb_small bb_green",
            click: function() {
                if ($("#dialog-name-theme-input").val() == "") {
                    alert("Please enter a name for your theme!")
                } else {
                    a.theme_name = $("#dialog-name-theme-input").val();
                    $("#dialog-name-theme-btn-save-changes").prop("disabled", true);
                    $("#dialog-name-theme-btn-cancel").hide();
                    $("#dialog-name-theme-btn-save-changes").text("Saving...");
                    $("#dialog-name-theme-btn-save-changes").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
                    $.ajax({
                        type: "POST",
                        async: true,
                        url: "save_theme.php",
                        data: {
                            tp: a
                        },
                        cache: false,
                        global: false,
                        dataType: "json",
                        error: function(d, b, c) {},
                        success: function(b) {
                            if (b.status == "ok") {
                                a.theme_id = b.theme_id;
                                $("#dialog-name-theme").dialog("close");
                                $("#dialog-theme-saved").dialog("open")
                            }
                        }
                    })
                }
            }
        }, {
            text: "Cancel",
            id: "dialog-name-theme-btn-cancel",
            "class": "btn_secondary_action",
            click: function() {
                $(this).dialog("close");
            }
        }]
    });
    $("#dialog-duplicate-theme").dialog({
        modal: true,
        autoOpen: false,
        closeOnEscape: false,
        width: 400,
        draggable: false,
        resizable: false,
        open: function() {
            $("#dialog-duplicate-theme-input").val(a.theme_name + " Copy")
        },
        buttons: [{
            text: "Save As Copy",
            id: "dialog-duplicate-theme-btn-save",
            "class": "bb_button bb_small bb_green",
            click: function() {
                $("#dropui_theme_options div.dropui-content").attr("style", "");
                if ($("#dialog-duplicate-theme-input").val() == "") {
                    alert("Please enter a name for the new theme!")
                } else {
                    a.theme_name = $("#dialog-duplicate-theme-input").val();
                    a.theme_id = 0;
                    $("#dialog-duplicate-theme-btn-save").prop("disabled", true);
                    $("#dialog-duplicate-theme-btn-cancel").hide();
                    $("#dialog-duplicate-theme-btn-save").text("Saving...");
                    $("#dialog-duplicate-theme-btn-save").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
                    $.ajax({
                        type: "POST",
                        async: true,
                        url: "save_theme.php",
                        data: {
                            tp: a
                        },
                        cache: false,
                        global: false,
                        dataType: "json",
                        error: function(d, b, c) {},
                        success: function(b) {
                            if (b.status == "ok") {
                                a.theme_id = b.theme_id;
                                $(this).dialog("close");
                                $("#dialog-theme-saved").dialog("open")
                            }
                        }
                    })
                }
            }
        }, {
            text: "Cancel",
            id: "dialog-duplicate-theme-btn-cancel",
            "class": "btn_secondary_action",
            click: function() {
                $("#dropui_theme_options div.dropui-content").attr("style", "");
                $(this).dialog("close")
            }
        }]
    });
    $("#dialog-rename-theme").dialog({
        modal: true,
        autoOpen: false,
        closeOnEscape: false,
        width: 400,
        draggable: false,
        resizable: false,
        open: function() {
            $("#dialog-rename-theme-input").val(a.theme_name)
        },
        buttons: [{
            text: "Rename Theme",
            id: "dialog-rename-theme-btn-save",
            "class": "bb_button bb_small bb_green",
            click: function() {
                $("#dropui_theme_options div.dropui-content").attr("style", "");
                if ($("#dialog-rename-theme-input").val() == "") {
                    alert("Please enter a new name for your theme!")
                } else {
                    a.theme_name = $("#dialog-rename-theme-input").val();
                    $("#dialog-rename-theme-btn-save").prop("disabled", true);
                    $("#dialog-rename-theme-btn-cancel").hide();
                    $("#dialog-rename-theme-btn-save").text("Saving...");
                    $("#dialog-rename-theme-btn-save").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
                    $.ajax({
                        type: "POST",
                        async: true,
                        url: "save_theme.php",
                        data: {
                            tp: a
                        },
                        cache: false,
                        global: false,
                        dataType: "json",
                        error: function(d, b, c) {},
                        success: function(b) {
                            if (b.status == "ok") {
                                a.theme_id = b.theme_id;
                                $(this).dialog("close");
                                $("#dialog-theme-saved").dialog("open")
                            }
                        }
                    })
                }
            }
        }, {
            text: "Cancel",
            id: "dialog-rename-theme-btn-cancel",
            "class": "btn_secondary_action",
            click: function() {
                $("#dropui_theme_options div.dropui-content").attr("style", "");
                $(this).dialog("close")
            }
        }]
    });
    $("#dialog-delete-theme").dialog({
        modal: true,
        autoOpen: false,
        closeOnEscape: false,
        width: 550,
        resizable: false,
        draggable: false,
        open: function() {
            $("#btn-theme-delete-ok").blur()
        },
        buttons: [{
            text: "Yes. Delete this theme",
            id: "btn-theme-delete-ok",
            "class": "bb_button bb_small bb_green",
            click: function() {
                $("#dropui_theme_options div.dropui-content").attr("style", "");
                $("#btn-theme-delete-ok").prop("disabled", true);
                $("#btn-theme-delete-cancel").hide();
                $("#btn-theme-delete-ok").text("Deleting...");
                $("#btn-theme-delete-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
                a.status = 0;
                $.ajax({
                    type: "POST",
                    async: true,
                    url: "save_theme.php",
                    data: {
                        tp: a
                    },
                    cache: false,
                    global: false,
                    dataType: "json",
                    error: function(d, b, c) {},
                    success: function(b) {
                        if (b.status == "ok") {
                            window.location.replace("edit_theme.php")
                        }
                    }
                })
            }
        }, {
            text: "Cancel",
            id: "btn-theme-delete-cancel",
            "class": "btn_secondary_action",
            click: function() {
                $("#dropui_theme_options div.dropui-content").attr("style", "");
                $(this).dialog("close")
            }
        }]
    });
    $("#dialog-share-theme").dialog({
        modal: true,
        autoOpen: false,
        closeOnEscape: false,
        width: 550,
        resizable: false,
        draggable: false,
        open: function() {
            $("#btn-theme-share-ok").blur()
        },
        buttons: [{
            text: "Save and Share this theme",
            id: "btn-theme-share-ok",
            "class": "bb_button bb_small bb_green",
            click: function() {
                $("#dropui_theme_options div.dropui-content").attr("style", "");
                $("#btn-theme-share-ok").prop("disabled", true);
                $("#btn-theme-share-cancel").hide();
                $("#btn-theme-share-ok").text("Processing...");
                $("#btn-theme-share-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
                a.theme_is_private = 0;
                $.ajax({
                    type: "POST",
                    async: true,
                    url: "save_theme.php",
                    data: {
                        tp: a
                    },
                    cache: false,
                    global: false,
                    dataType: "json",
                    error: function(d, b, c) {},
                    success: function(b) {
                        if (b.status == "ok") {
                            window.location.replace("edit_theme.php?theme_id=" + b.theme_id)
                        }
                    }
                })
            }
        }, {
            text: "Cancel",
            id: "btn-theme-share-cancel",
            "class": "btn_secondary_action",
            click: function() {
                $("#dropui_theme_options div.dropui-content").attr("style", "");
                $(this).dialog("close")
            }
        }]
    });
    $("#dialog-unshare-theme").dialog({
        modal: true,
        autoOpen: false,
        closeOnEscape: false,
        width: 550,
        resizable: false,
        draggable: false,
        open: function() {
            $("#btn-theme-unshare-ok").blur()
        },
        buttons: [{
            text: "Save and Set as Private",
            id: "btn-theme-unshare-ok",
            "class": "bb_button bb_small bb_green",
            click: function() {
                $("#dropui_theme_options div.dropui-content").attr("style", "");
                $("#btn-theme-unshare-ok").prop("disabled", true);
                $("#btn-theme-unshare-cancel").hide();
                $("#btn-theme-unshare-ok").text("Processing...");
                $("#btn-theme-unshare-ok").after("<div class='small_loader_box'><img src='images/loader_small_grey.gif' /></div>");
                a.theme_is_private = 1;
                $.ajax({
                    type: "POST",
                    async: true,
                    url: "save_theme.php",
                    data: {
                        tp: a,
                        unshare: 1
                    },
                    cache: false,
                    global: false,
                    dataType: "json",
                    error: function(d, b, c) {},
                    success: function(b) {
                        if (b.status == "ok") {
                            window.location.replace("edit_theme.php?theme_id=" + b.theme_id)
                        }
                    }
                })
            }
        }, {
            text: "Cancel",
            id: "btn-theme-unshare-cancel",
            "class": "btn_secondary_action",
            click: function() {
                $("#dropui_theme_options div.dropui-content").attr("style", "");
                $(this).dialog("close")
            }
        }]
    });
    $("#dialog-advanced-css").dialog({
        modal: true,
        autoOpen: false,
        closeOnEscape: false,
        width: 815,
        draggable: false,
        resizable: false,
        open: function() {
            $("#dialog-advanced-css-input").val(a.advanced_css)
        },
        buttons: [{
            text: "I'm Done Editing",
            id: "dialog-advanced-css-btn-save-changes",
            "class": "bb_button bb_small bb_green",
            click: function() {
                $("#dropui_theme_options div.dropui-content").attr("style", "");
                a.advanced_css = $("#dialog-advanced-css-input").val();
                $(this).dialog("close")
            }
        }, {
            text: "Cancel",
            id: "dialog-advanced-css-btn-cancel",
            "class": "btn_secondary_action",
            click: function() {
                $("#dropui_theme_options div.dropui-content").attr("style", "");
                $(this).dialog("close")
            }
        }]
    });
    $("#dialog-theme-saved").dialog({
        modal: true,
        autoOpen: false,
        closeOnEscape: false,
        width: 400,
        draggable: false,
        resizable: false,
        buttons: [{
            text: "OK",
            id: "dialog-theme-saved-btn-ok",
            "class": "bb_button bb_small bb_green",
            click: function() {
                window.location.replace("edit_theme.php?theme_id=" + a.theme_id)
            }
        }]
    });
    $("#button_save_theme").click(function() {
        if (a.theme_id == 0) {
            $("#dialog-name-theme").dialog("open")
        } else {
            if ($("#button_save_theme").text() != "Saving...") {
                $("#dropui_theme_options").hide();
                $("#button_save_theme").prop("disabled", true);
                $("#button_save_theme").text("Saving...");
                $("#button_save_theme").after("<div class='small_loader_box' style='float: right'><img src='images/loader_small_grey.gif' /></div>");
                $.ajax({
                    type: "POST",
                    async: true,
                    url: "save_theme.php",
                    data: {
                        tp: a
                    },
                    cache: false,
                    global: false,
                    dataType: "json",
                    error: function(d, b, c) {},
                    success: function(b) {
                        if (b.status == "ok") {
                            a.theme_id = b.theme_id;
                            $("#button_save_theme").text("Save Theme");
                            $("#button_save_theme").next().remove();
                            //$(this).dialog("close");
                            $("#dialog-theme-saved").dialog("open")
                        }
                    }
                })
            }
        }
        return false
    });
    $("#dialog-name-theme-form").submit(function() {
        $("#dialog-name-theme-btn-save-changes").click();
        return false
    });
    $("#advanced_css_link").click(function() {
        $("#dropui_theme_options div.dropui-content").hide();
        $("#dialog-advanced-css").dialog("open");
        return false
    });
    $("#duplicate_theme_link").click(function() {
        $("#dropui_theme_options div.dropui-content").hide();
        $("#dialog-duplicate-theme").dialog("open");
        return false
    });
    $("#dialog-duplicate-theme-form").submit(function() {
        $("#dialog-duplicate-theme-btn-save").click();
        return false
    });
    $("#rename_theme_link").click(function() {
        $("#dropui_theme_options div.dropui-content").hide();
        $("#dialog-rename-theme").dialog("open");
        return false
    });
    $("#dialog-rename-theme-form").submit(function() {
        $("#dialog-rename-theme-btn-save").click();
        return false
    });
    $("#delete_theme_link").click(function() {
        $("#dropui_theme_options div.dropui-content").hide();
        $("#dialog-delete-theme").dialog("open");
        return false
    });
    $("#ul_builtin_themes").delegate("li > a", "click", function(c) {
        var b = $(this).parent().data("theme_builtin_properties");
        b.theme_id = 0;
        $("#et_theme_preview").data("theme_properties", b);
        a = b;
        if ($(this).parent().data("font_link_loaded") !== 1) {
            $("head").append($(this).parent().data("font_link"));
            $(this).parent().data("font_link_loaded", 1)
        }
        reload_theme();
        return false
    });
    $("#set_public_theme_link").click(function() {
        $("#dropui_theme_options div.dropui-content").hide();
        $("#dialog-share-theme").dialog("open");
        return false
    });
    $("#set_private_theme_link").click(function() {
        $("#dropui_theme_options div.dropui-content").hide();
        $("#dialog-unshare-theme").dialog("open");
        return false
    });
    if (is_support_html5_uploader()) {
        $("#et_form_logo_file,#et_wallpaper_custom_bg_file,#et_headerbg_custom_bg_file,#et_form_formbg_custom_file,#et_form_highlightbg_custom_file,#et_form_guidelinesbg_custom_file,#et_form_fieldbg_custom_file").uploadifive({
            uploadScript: "upload_theme_images.php",
            buttonText: "Select File",
            removeCompleted: true,
            formData: {
                session_id: $("#et_theme_preview").data("session_id"),
                uploader_origin: ""
            },
            auto: true,
            multi: false,
            onUploadError: function(b, e, d, c) {
                alert("The file " + b.name + " could not be uploaded: " + c)
            },
            onAddQueueItem: function(c) {
                var b = $(this).attr("id");
                $(this).data("uploadifive").settings.formData = {
                    session_id: $("#et_theme_preview").data("session_id"),
                    uploader_origin: b
                }
            },
            onUploadComplete: function(d, c) {
                var b = false;
                try {
                    var g = jQuery.parseJSON(c);
                    b = true
                } catch (h) {
                    b = false;
                    alert(c)
                }
                if (b == true && g.status == "ok") {
                    var f = $("#et_theme_preview").data("theme_properties");
                    if (g.uploader_origin == "et_form_logo_file") {
                        $("#et_your_logo_url").val(g.image_url);
                        if (g.image_height > 0) {
                            $("#et_your_logo_height").val(g.image_height)
                        }
                        $("#et_your_logo_submit").click()
                    } else {
                        if (g.uploader_origin == "et_wallpaper_custom_bg_file") {
                            $("#et_wallpaper_custom_bg").val(g.image_url);
                            $("#et_wallpaper_custom_bg_submit").click()
                        } else {
                            if (g.uploader_origin == "et_headerbg_custom_bg_file") {
                                $("#et_headerbg_custom_bg").val(g.image_url);
                                $("#et_headerbg_custom_bg_submit").click()
                            } else {
                                if (g.uploader_origin == "et_form_formbg_custom_file") {
                                    $("#et_formbg_custom_bg").val(g.image_url);
                                    $("#et_formbg_custom_bg_submit").click()
                                } else {
                                    if (g.uploader_origin == "et_form_highlightbg_custom_file") {
                                        $("#et_highlightbg_custom_bg").val(g.image_url);
                                        $("#et_highlightbg_custom_bg_submit").click()
                                    } else {
                                        if (g.uploader_origin == "et_form_guidelinesbg_custom_file") {
                                            $("#et_guidelinesbg_custom_bg").val(g.image_url);
                                            $("#et_guidelinesbg_custom_bg_submit").click()
                                        } else {
                                            if (g.uploader_origin == "et_form_fieldbg_custom_file") {
                                                $("#et_fieldbg_custom_bg").val(g.image_url);
                                                $("#et_fieldbg_custom_bg_submit").click()
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    alert("Error uploading image. Please try again.")
                }
            }
        })
    } else {
        if ($.browser.flash == true) {
            $("#et_form_logo_file,#et_wallpaper_custom_bg_file,#et_headerbg_custom_bg_file,#et_form_formbg_custom_file,#et_form_highlightbg_custom_file,#et_form_guidelinesbg_custom_file,#et_form_fieldbg_custom_file").uploadify({
                uploader: "js/uploadify/uploadify.swf",
                script: "upload_theme_images.php",
                height: 31,
                width: 151,
                cancelImg: "images/icons/stop.png",
                removeCompleted: true,
                displayData: "percentage",
                scriptData: {
                    session_id: $("#et_theme_preview").data("session_id"),
                    uploader_origin: ""
                },
                auto: true,
                multi: false,
                buttonImg: "images/upload_black.png",
                onError: function(e, b, d, c) {
                    alert("Error uploading image" + c)
                },
                onSelectOnce: function(c, d) {
                    var b = $(c.target).attr("id");
                    $("#" + b).uploadifySettings("scriptData", {
                        uploader_origin: b
                    })
                },
                onComplete: function(c, k, g, h, i) {
                    var d = false;
                    try {
                        var b = jQuery.parseJSON(h);
                        d = true
                    } catch (j) {
                        d = false;
                        alert(h)
                    }
                    if (d == true && b.status == "ok") {
                        var f = $("#et_theme_preview").data("theme_properties");
                        if (b.uploader_origin == "et_form_logo_file") {
                            $("#et_your_logo_url").val(b.image_url);
                            if (b.image_height > 0) {
                                $("#et_your_logo_height").val(b.image_height)
                            }
                            $("#et_your_logo_submit").click()
                        } else {
                            if (b.uploader_origin == "et_wallpaper_custom_bg_file") {
                                $("#et_wallpaper_custom_bg").val(b.image_url);
                                $("#et_wallpaper_custom_bg_submit").click()
                            } else {
                                if (b.uploader_origin == "et_headerbg_custom_bg_file") {
                                    $("#et_headerbg_custom_bg").val(b.image_url);
                                    $("#et_headerbg_custom_bg_submit").click()
                                } else {
                                    if (b.uploader_origin == "et_form_formbg_custom_file") {
                                        $("#et_formbg_custom_bg").val(b.image_url);
                                        $("#et_formbg_custom_bg_submit").click()
                                    } else {
                                        if (b.uploader_origin == "et_form_highlightbg_custom_file") {
                                            $("#et_highlightbg_custom_bg").val(b.image_url);
                                            $("#et_highlightbg_custom_bg_submit").click()
                                        } else {
                                            if (b.uploader_origin == "et_form_guidelinesbg_custom_file") {
                                                $("#et_guidelinesbg_custom_bg").val(b.image_url);
                                                $("#et_guidelinesbg_custom_bg_submit").click()
                                            } else {
                                                if (b.uploader_origin == "et_form_fieldbg_custom_file") {
                                                    $("#et_fieldbg_custom_bg").val(b.image_url);
                                                    $("#et_fieldbg_custom_bg_submit").click()
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        alert("Error uploading image. Please try again.")
                    }
                }
            })
        }
    }
    $("#et_form_logo_more,#et_wallpaper_custom_bg_more,#et_headerbg_custom_bg_more,#et_form_formbg_custom_more,#et_form_highlightbg_custom_more,#et_form_guidelinesbg_custom_more,#et_form_fieldbg_custom_more").click(function() {
        if ($(this).text() == "more options") {
            $(this).parent().prev().slideDown();
            $(this).text("hide options")
        } else {
            $(this).parent().prev().slideUp();
            $(this).text("more options")
        }
        return false
    });
    $("#theme_editor_loading").fadeOut()
});