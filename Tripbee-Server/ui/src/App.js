import React, { Component } from 'react';
import openSocket from 'socket.io-client';

import './App.css';
import logo from './assets/sap-logo-svg.svg';
import ChatList, { chatEvent } from './ChatList';


import "@ui5/webcomponents-base/src/sap/ui/webcomponents/base/browsersupport/Edge";
import "@ui5/webcomponents/dist/Button";
import "@ui5/webcomponents/dist/ShellBar";
import "@ui5/webcomponents/dist/ShellBarItem";
import "@ui5/webcomponents/dist/Title";
import "@ui5/webcomponents/dist/Input";
import "@ui5/webcomponents/dist/DatePicker";
import "@ui5/webcomponents/dist/List";
import "@ui5/webcomponents/dist/CustomListItem";
import "@ui5/webcomponents/dist/Panel";
import "@ui5/webcomponents/dist/Dialog";
import "@ui5/webcomponents/dist/Label";
import "@ui5/webcomponents/dist/TextArea";
import "@ui5/webcomponents/dist/Timeline";
import "@ui5/webcomponents/dist/Card";

const devices = {
  WATCH_1: 'watch_1',
  WATCH_2: 'watch_2',
  PHONE: 'phone',
};

class App extends Component {
  state = {
    addToWatch1: undefined,
    addToWatch2: undefined,
    addToPhone: undefined,
    infoWatch1: undefined,
    infoWatch2: undefined,
    infoPhone: undefined,
    endpoint: window.location.href.search("ui.cfapps") === -1 ? "https://hands-free-fieldservice-api-dev.cfapps.eu10.hana.ondemand.com" : "https://hands-free-fieldservice-api.cfapps.eu10.hana.ondemand.com",
    connected: false
  };


  eventChatListener(event, idDevice, data) {
    if (event === chatEvent.ADD_CHAT) {
      console.log("------Add Chat Listener---------");
      console.log(idDevice);
      switch (idDevice) {
        case devices.WATCH_1:
          this.setState({ addToWatch1: data.functionAdd });
          this.setState({ infoWatch1: data.functionInfo });
          break;
        case devices.WATCH_2:
          this.setState({ addToWatch2: data.functionAdd });
          this.setState({ infoWatch2: data.functionInfo });
          break;
        case devices.PHONE:
          this.setState({ addToPhone: data.functionAdd });
          this.setState({ infoPhone: data.functionInfo });
          break;
        default:
          console.log("UNKNOWN DEVICE!!!!!! " + idDevice)
      }
    }
    if (event === chatEvent.CLOSE_CHAT) {
      if (idDevice === devices.WATCH_2 || idDevice === devices.PHONE) {
        var currentBox;
        var otherBox;
        if (idDevice === devices.WATCH_2) {
          currentBox = '2';
          otherBox = '3';
        } else {
          currentBox = '3';
          otherBox = '2';
        }
        if (!document.querySelector(`#chat_${currentBox}.flex-chat-section.collapsible`).classList.contains("collapsed")) {
          document.querySelector(`#chat_${currentBox}.flex-chat-section.collapsible`).classList.toggle('collapsed');
          if (document.querySelector(`#chat_${otherBox}.flex-chat-section.collapsible`).classList.contains("collapsed")) {
            document.querySelector(`#chat_1 .scrollableAndShowBottom`).classList.remove('scrollableAndShowBottom2Row');
            document.querySelector(`#chat_2 .scrollableAndShowBottom`).classList.remove('scrollableAndShowBottom2Row');
            document.querySelector(`#chat_3 .scrollableAndShowBottom`).classList.remove('scrollableAndShowBottom2Row');
            document.querySelector(`#chat_1 .scrollableAndShowBottom`).classList.add('scrollableAndShowBottom1Row');
            document.querySelector(`#chat_2 .scrollableAndShowBottom`).classList.add('scrollableAndShowBottom1Row');
            document.querySelector(`#chat_3 .scrollableAndShowBottom`).classList.add('scrollableAndShowBottom1Row');
          } else {
            document.querySelector(`#chat_1 .scrollableAndShowBottom`).classList.add('scrollableAndShowBottom2Row');
            document.querySelector(`#chat_2 .scrollableAndShowBottom`).classList.add('scrollableAndShowBottom2Row');
            document.querySelector(`#chat_3 .scrollableAndShowBottom`).classList.add('scrollableAndShowBottom2Row');
          }
        }
      }
    }
  }

  resetChatBox() {
    this.state.infoWatch1 && this.state.infoWatch1({ reset: true });
    this.state.infoWatch2 && this.state.infoWatch2({ reset: true });
    this.state.infoPhone && this.state.infoPhone({ reset: true });
    document.querySelector(`#chat_2.flex-chat-section.collapsible`).classList.add('collapsed');
    document.querySelector(`#chat_3.flex-chat-section.collapsible`).classList.add('collapsed');
    document.querySelector(`#chat_1 .scrollableAndShowBottom`).classList.remove('scrollableAndShowBottom2Row');
    document.querySelector(`#chat_2 .scrollableAndShowBottom`).classList.remove('scrollableAndShowBottom2Row');
    document.querySelector(`#chat_3 .scrollableAndShowBottom`).classList.remove('scrollableAndShowBottom2Row');
    document.querySelector(`#chat_1 .scrollableAndShowBottom`).classList.add('scrollableAndShowBottom1Row');
    document.querySelector(`#chat_2 .scrollableAndShowBottom`).classList.add('scrollableAndShowBottom1Row');
    document.querySelector(`#chat_3 .scrollableAndShowBottom`).classList.add('scrollableAndShowBottom1Row');
  }

  findChatBox(response) {

    var infoBox1;
    var infoBox2;
    var infoBox3;

    if (this.state.infoWatch1) {
      infoBox1 = this.state.infoWatch1();
      if (infoBox1.deviceId === response.device) {
        return {
          info: this.state.infoWatch1,
          add: this.state.addToWatch1
        }
      }
    }
    if (this.state.infoWatch2) {
      infoBox2 = this.state.infoWatch2();
      if (infoBox2.deviceId === response.device) {
        return {
          info: this.state.infoWatch2,
          add: this.state.addToWatch2
        }
      }
    }
    if (this.state.infoPhone) {
      infoBox3 = this.state.infoPhone();
      if (infoBox3.deviceId === response.device) {
        return {
          info: this.state.infoPhone,
          add: this.state.addToPhone
        }
      }
    }
    if (infoBox1.chatIsFree) {
      return {
        info: this.state.infoWatch1,
        add: this.state.addToWatch1
      }
    }
    if (infoBox2.chatIsFree) {
      return {
        info: this.state.infoWatch2,
        add: this.state.addToWatch2
      }
    }
    if (infoBox3.chatIsFree) {
      return {
        info: this.state.infoPhone,
        add: this.state.addToPhone
      }
    }

    return undefined;
  }

  handleData(data) {
    if (data && data.data) {
      var response = data.data;
      const regexReset = /(ReSeT)/;
      var regex = regexReset.exec(response.message);
      if (response.reset || regex) {
        this.resetChatBox();
        return;
      }
      var chatBox = this.findChatBox(response);
      if (!chatBox) {
        console.log("WARNING! No chat seems to be free");
        console.log(data);
        return;
      }
      var chatInfo = chatBox.info();
      if (chatInfo.chatListenerIdentifier === devices.WATCH_2 || chatInfo.chatListenerIdentifier === devices.PHONE) {
        var currentBox;
        var otherBox;
        if (chatInfo.chatListenerIdentifier === devices.WATCH_2) {
          currentBox = '2';
          otherBox = '3';
        } else {
          currentBox = '3';
          otherBox = '2';
        }
        if (document.querySelector(`#chat_${currentBox}.flex-chat-section.collapsible`).classList.contains("collapsed")) {
          document.querySelector(`#chat_${currentBox}.flex-chat-section.collapsible`).classList.toggle('collapsed');
          document.querySelector(`#chat_1 .scrollableAndShowBottom`).classList.remove('scrollableAndShowBottom1Row');
          document.querySelector(`#chat_2 .scrollableAndShowBottom`).classList.remove('scrollableAndShowBottom1Row');
          document.querySelector(`#chat_3 .scrollableAndShowBottom`).classList.remove('scrollableAndShowBottom1Row');
          if (document.querySelector(`#chat_${otherBox}.flex-chat-section.collapsible`).classList.contains("collapsed")) {
            document.querySelector(`#chat_1 .scrollableAndShowBottom`).classList.add('scrollableAndShowBottom2Row');
            document.querySelector(`#chat_2 .scrollableAndShowBottom`).classList.add('scrollableAndShowBottom2Row');
            document.querySelector(`#chat_3 .scrollableAndShowBottom`).classList.add('scrollableAndShowBottom2Row');
          } else {
            document.querySelector(`#chat_1 .scrollableAndShowBottom`).classList.remove('scrollableAndShowBottom2Row');
            document.querySelector(`#chat_2 .scrollableAndShowBottom`).classList.remove('scrollableAndShowBottom2Row');
            document.querySelector(`#chat_3 .scrollableAndShowBottom`).classList.remove('scrollableAndShowBottom2Row');
          }
        }
      }
      if (chatInfo.chatIsFree) {
        chatInfo.deviceId = response.device;
        chatInfo.ticketNo = response.serviceCallCode;
        chatInfo.chatIsFree = false;
        chatBox.info(chatInfo);
      }
      chatBox.add && chatBox.add(response);
    }
  }

  componentDidMount() {
    const { endpoint } = this.state;
    const socket = openSocket(endpoint);
    socket.on("message", data => this.handleData({ data }));
    socket.on("connect", data => this.setState({ connected: true }));
    socket.on("disconnect", data => this.setState({ connected: false }));
    var that = this;
    document.querySelector(`ui5-shellbar`).addEventListener("press", function (event) {
      if (event && event.target && (event.target.id === 'conn' || event.target.id === 'disc')) {
        fetch(`${that.state.endpoint}/reset`, {
          method: 'POST',
        });
      }
    });
  }

  render() {
    return (
      <div className="app">
        <ui5-shellbar
          logo={logo}
        //secondary-title="Maintenance Assistant - Conversation Logs"
        >
          {!this.state.connected && <ui5-shellbar-item data-ui5-slot="items" id="disc" src="sap-icon://disconnected" text="Disconnect"></ui5-shellbar-item>}
          {this.state.connected && <ui5-shellbar-item data-ui5-slot="items" id="conn" src="sap-icon://connected" text="Connect"></ui5-shellbar-item>}
        </ui5-shellbar>
        <h2 className="customShellbarTitle">Maintenance Assistant - Conversation Logs</h2>
        <section className="app-content">
          <div className="flex-chat-container">
            <div id="chat_1" className="flex-chat-section collapsible">
              <ChatList chatListener={this.eventChatListener.bind(this)} chatListenerIdentifier={devices.WATCH_1} runDemo={true} />
            </div>
            <div id="chat_2" className="flex-chat-section collapsible collapsed">
              <ChatList chatListener={this.eventChatListener.bind(this)} chatListenerIdentifier={devices.WATCH_2} runDemo={false} />
            </div>
            <div id="chat_3" className="flex-chat-section collapsible collapsed">
              <ChatList chatListener={this.eventChatListener.bind(this)} chatListenerIdentifier={devices.PHONE} runDemo={false} />
            </div>
          </div>
        </section>
      </div>
    );
  }
}

export default App;
