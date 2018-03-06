import TeachResources from './teach-resources';
import CourseShare from './course-share';

let _teachResources = new TeachResources();
_teachResources.init();

let _courseShare = new CourseShare();
_courseShare.init();

if ($(".nav.nav-tabs").length > 0) {
  $(".nav.nav-tabs").lavaLamp();
};