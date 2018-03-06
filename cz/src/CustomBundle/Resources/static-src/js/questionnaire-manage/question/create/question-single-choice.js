import QuestionChoice from './question-choice';
import ReactDOM from 'react-dom';
import React from 'react';
import QuestionOptions from '../question-options/question';

class SingleChoice extends QuestionChoice {
  initOptions() {
    ReactDOM.render( <QuestionOptions imageUploadUrl={this.imageUploadUrl} imageDownloadUrl={this.imageDownloadUrl} dataSource={this.dataSource} dataAnswer={this.dataAnswer}  isRadio={true}/>,
      document.getElementById('question-options')
    );
  }
}

export default SingleChoice;