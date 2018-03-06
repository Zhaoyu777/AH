import baseRoutes from './base';
import courseRoutes from './course';
import signInRoutes from './signIn';
import activityRoutes from './activity';
import myRoutes from './my';
import groupRoutes from './group';
import learningActivityRoutes from './learning-activity';

const routes = [
  ...baseRoutes,
  ...courseRoutes,
  ...signInRoutes,
  ...activityRoutes,
  ...myRoutes,
  ...groupRoutes,
  ...learningActivityRoutes];

export default routes;
