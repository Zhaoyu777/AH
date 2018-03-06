<?php
namespace CustomBundle\Controller\Questionnaire;

use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\BaseController;

class ManageController extends BaseController
{
    public function indexAction(Request $request, $id)
    {
        $courseSet = $this->getCourseSetService()->tryManageCourseSet($id);

        $conditions = array(
            'courseSetId' => $courseSet['id'],
        );
        $paginator = new Paginator(
            $this->get('request'),
            $this->getQuestionnaireService()->searchQuestionnaireCount($conditions),
            10
        );
        $questionnaires = $this->getQuestionnaireService()->searchQuestionnaires(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $userIds = ArrayToolkit::column($questionnaires, 'updatedUserId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render(
            'questionnaire-manage/index.html.twig',
            array(
                'questionnaires' => $questionnaires,
                'users'          => $users,
                'courseSet'      => $courseSet,
                'paginator'      => $paginator,
            )
        );
    }

    public function createAction(Request $request, $id)
    {
        $courseSet = $this->getCourseSetService()->tryManageCourseSet($id);
        if ('POST' == $request->getMethod()) {
            $user = $this->getCurrentUser();
            $fields = $request->request->all();
            $fields['courseSetId'] = $courseSet['id'];
            $fields['updatedUserId'] = $user['id'];

            $questionnaire = $this->getQuestionnaireService()->createQuestionnaire($fields);
            return $this->redirect($this->generateUrl('questionnaire_question', array('questionnaireId' => $questionnaire['id'])));
        }

        return $this->render('questionnaire-manage/edit.html.twig', array(
            'courseSet' => $courseSet,
        ));
    }

    public function updateAction(Request $request, $id)
    {
        $questionnaire = $this->getQuestionnaireService()->getQuestionnaire($id);
        $courseSet = $this->getCourseSetService()->tryManageCourseSet($questionnaire['courseSetId']);
        if ('POST' == $request->getMethod()) {
            $fields = $request->request->all();
            $this->getQuestionnaireService()->updateQuestionnaire($questionnaire['id'], $fields);

            return $this->redirect($this->generateUrl('course_set_manage_questionnaire', array('id' => $courseSet['id'])));
        }

        return $this->render('questionnaire-manage/edit.html.twig', array(
            'courseSet' => $courseSet,
            'questionnaire' => $questionnaire
        ));
    }

    public function deleteAction($id)
    {
        $this->getQuestionnaireService()->deleteQuestionnaire($id);

        return $this->createJsonResponse(true);
    }

    public function deletesAction(Request $request)
    {
        $ids = $request->request->get('ids');
        $this->getQuestionnaireService()->deleteQuestionnaires($ids);

        return $this->createJsonResponse(true);
    }

    public function questionAction(Request $request, $questionnaireId)
    {
        $questionnaire = $this->getQuestionnaireService()->getQuestionnaire($questionnaireId);
        if (empty($questionnaire)) {
            throw $this->createResourceNotFoundException('questionnaire', $questionnaireId);
        }

        $courseSet = $this->getCourseSetService()->tryManageCourseSet($questionnaire['courseSetId']);

        $questions = $this->getQuestionService()->findQuestionsByQuestionnaireId(
            $questionnaireId,
            0,
            PHP_INT_MAX
        );
        $user = $this->getUserService()->getUser($questionnaire['updatedUserId']);

        return $this->render('questionnaire-manage/question/questions.html.twig', array(
            'questions' => $questions,
            'questionnaire' => $questionnaire,
            'courseSet' => $courseSet,
            'user' => $user
        ));
    }

    public function questionCreateAction(Request $request, $questionnaireId, $type)
    {
        $questionnaire = $this->getQuestionnaireService()->getQuestionnaire($questionnaireId);
        $courseSet = $this->getCourseSetService()->tryManageCourseSet($questionnaire['courseSetId']);
        if ('POST' == $request->getMethod()) {
            $data = $request->request->all();
            $data['questionnaireId'] = $questionnaire['id'];
            $data['metas'] = empty($data['choices']) ? '' : $data['choices'];
            $question = $this->getQuestionService()->createQuestion($data);

            if ($data['submission'] === 'continue') {
                $urlParams['questionnaireId'] = $questionnaireId;
                $urlParams['type'] = $type;
                $this->setFlashMessage('success', '题目添加成功，请继续添加。');

                return $this->redirect($this->generateUrl('questionnaire_question_create', $urlParams));
            }

            $this->setFlashMessage('success', '题目添加成功。');

            return $this->redirect($this->generateUrl('questionnaire_question', array(
                'questionnaireId' => $questionnaireId)));
        }


        return $this->render('questionnaire-manage/question/create.html.twig', array(
            'courseSet' => $courseSet,
            'type' => $type,
            'questionnaire' => $questionnaire,
        ));
    }

    public function questionDeleteAction(Request $request, $id)
    {
        $this->getQuestionService()->deleteQuestion($id);

        return $this->createJsonResponse(true);
    }

    public function questionDeletesAction(Request $request, $questionnaireId)
    {
        $ids = $request->request->get('ids');
        $this->getQuestionService()->deleteQuestions($questionnaireId, $ids);

        return $this->createJsonResponse(true);
    }

    public function questionSortAction(Request $request, $questionnaireId)
    {
        $ids = $request->request->get('ids');

        $questionnaire = $this->getQuestionnaireService()->getQuestionnaire($questionnaireId);
        $this->getQuestionService()->sortQuestions($questionnaire['id'], $ids);

        return $this->createJsonResponse(true);
    }

    protected function getQuestionnaireService()
    {
        return $this->createService('CustomBundle:Questionnaire:QuestionnaireService');
    }

    protected function getQuestionService()
    {
        return $this->createService('CustomBundle:Questionnaire:QuestionService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }
}
