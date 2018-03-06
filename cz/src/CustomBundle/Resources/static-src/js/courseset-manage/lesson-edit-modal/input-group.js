import React, { Component } from 'react';
import { trim } from 'app/common/unit';
import Options from 'app/common/component/multi-input/options';
import postal from 'postal';
import notify from "common/notify";

export default class InputGroup extends Component {
  constructor(props) {
    super(props);
    this.state = {
      itemName: "",
      searched: true,
      resultful: false,
      searchResult: [],
      addArr: [],
    }
    this.subscribeMessage();
  }

  subscribeMessage() {
    postal.subscribe({
      channel: "courseInfoMultiInput",
      topic: "addMultiInput",
      callback: () => {
        console.log('add');
        this.handleAdd();
      }
    });
  }

  handleAdd() {
    if (trim(this.state.itemName).length > 0) {
      if (trim(this.state.itemName).length > 1000) {
        notify('danger', '最大字数为1000字');
      } else if(this.context.dataSourceUi.length >= 20) {
        notify('danger', '最多可添加20条');
      } else {
        this.state.addArr.push(this.state.itemName);
        // 新增id都是0
        this.context.addItem({content:this.state.itemName, id: 0}, this.state.itemData);
      }
    }
    this.setState({
      itemName: '',
      searchResult: [],
      resultful: false,
    })
  }

  onFocus(event) {
    //在这种情况下，重新开启搜索功能；
    this.setState({
      searched: true,
    })
  }

  handleNameChange(event) {
    // let value = trim(event.currentTarget.value);
    let value = event.currentTarget.value;
    this.setState({
      itemName: value,
      searchResult: [],
      resultful: false,
    });

    if (!this.context.searchable.enable || value.length < 0 || !this.state.searched) {
      return;
    }

    setTimeout(() => {
      send(this.context.searchable.url + value, searchResult => {
        if (this.state.itemName.length > 0) {
          console.log({ 'searchResult': searchResult });
          this.setState({
            searchResult: searchResult,
            resultful: true,
          });
        }
      });
    }, 100)
  }

  render() {
    let createTrans = Translator.trans('site.data.create');
    return (
      <div className="input-group">
        <input className="form-control" value={this.state.itemName} onChange={event => this.handleNameChange(event)} onFocus={event => this.onFocus(event)} />
        {this.context.searchable.enable && this.state.resultful && <Options searchResult={this.state.searchResult} selectChange={(event, name) => this.selectChange(event, name)} resultful={this.state.resultful} />}
        {this.context.addable && <span className="input-group-btn"><a className="btn btn-default" onClick={() => this.handleAdd()}>{createTrans}</a></span>}
      </div>
    );
  }
}

InputGroup.contextTypes = {
  addItem: React.PropTypes.func,
  addable: React.PropTypes.bool,
  searchable:  React.PropTypes.shape({
    enable: React.PropTypes.bool,
    url: React.PropTypes.string,
  }),
  dataSourceUi: React.PropTypes.array,
};
