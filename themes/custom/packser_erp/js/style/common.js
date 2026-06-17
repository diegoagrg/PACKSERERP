var set_section_height = function () {
  var content_section = jQuery('.main-container > .row > section');
  var content_section_offset = content_section.offset();
  var content_section_top = content_section_offset.top;
  var window_height = window.innerHeight;
  content_section.css('height', window_height - content_section_top);
}

jQuery (function () {
  set_section_height();
});
