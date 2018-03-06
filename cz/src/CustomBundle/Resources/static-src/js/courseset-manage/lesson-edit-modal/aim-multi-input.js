import React, { Component } from 'react';
import MultiInput from 'app/common/component/multi-input';
import { getRandomString } from 'app/common/component/multi-input/part';
import InputGroup from './input-group';


function initItem(dataSourceUi, value) {
  let item = {
    itemId: getRandomString(),
    label: value.content,
    outputValue: value.content,
    id: value.id,
  };
  dataSourceUi.push(item);
}

function removeItem(dataSourceUi, removeList, itemId) {
  for (let i = 0; i < dataSourceUi.length; i++) {
    if (dataSourceUi[i].itemId == itemId) {
      console.log(removeList);
      removeList.push({content: dataSourceUi[i].outputValue, id: dataSourceUi[i].id});
      dataSourceUi.splice(i, 1);
      break;
    }
  }
}

function updateItemSeq(sortDatas, dataSourceUi) {
  let temps = [];
  for (let i = 0; i < sortDatas.length; i++) {
    for (let j = 0; j < dataSourceUi.length; j++) {
      if (sortDatas[i] == dataSourceUi[j].itemId) {
        temps.push(dataSourceUi[j]);
        break;
      }
    }
  }
  return temps;
}

export default class PersonaMultiInput extends MultiInput {
  constructor(props) {
    super(props);

  }

  componentWillMount() {
    this.state = {
      dataSourceUi: [],
      removeList: [],
    }
    this.props.dataSource.map((item, index) => {
      initItem(this.state.dataSourceUi, item);
    })

    // console.log('initItem after123',this.state.dataSourceUi, this.state.removeList)
  }

  getChildContext() {
    return {
      removeItem: this.removeItem,
      sortItem: this.sortItem,
      addItem: this.addItem,
      addable: this.props.addable,
      searchable: this.props.searchable,
      sortable: this.props.sortable,
      listClassName:this.props.listClassName,
      inputName: this.props.inputName,
      dataSourceUi: this.state.dataSourceUi,
    }
  }

  removeItem = (event) => {
    let id = event.currentTarget.attributes["data-item-id"].value;
    removeItem(this.state.dataSourceUi, this.state.removeList, id);
    this.setState({
      dataSourceUi: this.state.dataSourceUi,
    });
  }

  sortItem = (datas) => {
    this.state.dataSourceUi = updateItemSeq(datas, this.state.dataSourceUi);
    this.setState({
      dataSourceUi: this.state.dataSourceUi,
    });
  }

  addItem = (value, data) => {
    initItem(this.state.dataSourceUi, value);
    this.setState({
      dataSourceUi: this.state.dataSourceUi,
    });
  }

  getOutputSets() {
    //应该优化成表单数据进行填充
    let outputSets = [];
    this.state.dataSourceUi.map((item, index) => {
      outputSets.push({content: item.outputValue, id: item.id});
    })
    return outputSets;

  }

  render() {
    let list = this.getList();
    let outputSets = this.getOutputSets();
    return (
      <div className="multi-group">
        {list}
        {this.props.showAddBtnGroup && <InputGroup/>}
        <input type='hidden' name={this.props.outputDataElement} value={JSON.stringify(outputSets)} />
        <input type='hidden' name={`delete${this.props.outputDataElement}`} value={JSON.stringify(this.state.removeList)} />
      </div>
    );
  }
}

PersonaMultiInput.propTypes = {
  ...MultiInput.propTypes,

};

PersonaMultiInput.defaultProps = {
  ...MultiInput.defaultProps,
};

PersonaMultiInput.childContextTypes = {
  ...MultiInput.childContextTypes,
};

