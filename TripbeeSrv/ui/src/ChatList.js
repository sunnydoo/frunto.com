import React, { Component } from 'react';
import ChatItem from './ChatItem';

export const chatEvent = {
  ADD_CHAT: 'add_chat',
  CLOSE_CHAT: 'close_chat',
};


const randomMessageInterval = 1000;
const randomMessageIntervalStart = 30*1000;

const indexArray = function(param) {
  var a = [], i;
  for (i=0; i<param.length; i+=1) {
      a[param[i].index] = {bot: param[i].bot, showAfterSeconds: param[i].showAfterSeconds, message: param[i].message};
  }
  return a;
}

const demoStep = {
  START: 'start',
  ORDER_SPARE_PART: 'order_spare_part',
  WHICH_SPARE_PART: 'which_spare_part',
  ORDER_TONER: 'order_toner',
  ORDER_CREATED: 'order_created',
  MORE_TO_DO: 'more_to_do',
  NOTHING_MORE_TO_DO: 'nothing_more_to_do',
  GOODBYE: 'goodbye',
  MAINTENANCE: 'maintenance',
  PRINTER_ON: 'printer_on',
  YES_PRINTER_ON: 'yes_printer_on',
  WHAT_CABLES: 'what_cable',
  BOTH_CABLES: 'both_cables',
  LED_ON: 'led_on',
  YES_LED_ON: 'yes_led_on',
  MORE_COMPLEX: 'more_complex',
  END: 'end'
};

const demoSteps = indexArray([
  {index: demoStep.START, bot: true, showAfterSeconds: 0, message: 'For your current appointment, do you need guided maintenance or do you wish to order a spare part?'},
  {index: demoStep.ORDER_SPARE_PART, bot: false, showAfterSeconds: 5, message: 'Order a spare part'},
  {index: demoStep.WHICH_SPARE_PART, bot: true, showAfterSeconds: 2, message: 'Which spare part do you want to order for HP Color LaserJet E57540?'},
  {index: demoStep.ORDER_TONER, bot: false, showAfterSeconds: 5, message: 'Toner'},
  {index: demoStep.ORDER_CREATED, bot: true, showAfterSeconds: 1, message: 'Order #113 created.'},
  {index: demoStep.MORE_TO_DO, bot: true, showAfterSeconds: 0, message: 'Okay. Is there anything else I can do for you?'},
  {index: demoStep.NOTHING_MORE_TO_DO, bot: false, showAfterSeconds: 3, message: 'No'},
  {index: demoStep.GOODBYE, bot: true, showAfterSeconds: 1, message: 'Goodbye and hope to hear you soon!'},
  {index: demoStep.MAINTENANCE, bot: false, showAfterSeconds: 4, message: 'I need maintenance'},
  {index: demoStep.PRINTER_ON, bot: true, showAfterSeconds: 2, message: 'Is the printer on?'},
  {index: demoStep.YES_PRINTER_ON, bot: false, showAfterSeconds: 2, message: 'Yes'},
  {index: demoStep.WHAT_CABLES, bot: true, showAfterSeconds: 2, message: 'What cables are plugged in?'},
  {index: demoStep.BOTH_CABLES, bot: false, showAfterSeconds: 4, message: 'Power cable and Network cable'},
  {index: demoStep.LED_ON, bot: true, showAfterSeconds: 2, message: 'Are Ethernet LEDs blinking?'},
  {index: demoStep.YES_LED_ON, bot: false, showAfterSeconds: 2, message: 'Yes'},
  {index: demoStep.MORE_COMPLEX, bot: true, showAfterSeconds: 1, message: 'It looks like it\'s a more complex problem. Go to the SAP CX Innovation Office Team. They can help you.'},
]);

const demo = [demoStep.START, demoStep.ORDER_SPARE_PART, demoStep.WHICH_SPARE_PART, demoStep.ORDER_TONER, demoStep.ORDER_CREATED, demoStep.MORE_TO_DO, demoStep.NOTHING_MORE_TO_DO, demoStep.GOODBYE, demoStep.END,
  demoStep.START, demoStep.MAINTENANCE, demoStep.PRINTER_ON, demoStep.YES_PRINTER_ON, demoStep.WHAT_CABLES, demoStep.BOTH_CABLES, demoStep.LED_ON, demoStep.YES_LED_ON, demoStep.MORE_COMPLEX, demoStep.END];

const DEVICE_ID_NOT_SET = "DEVICE_ID_NOT_SET";

class ChatList extends Component {

    constructor(props) {
      super(props);
      this.state = {
        chatIsFree: true,
        deviceId: DEVICE_ID_NOT_SET,
        deviceName: "No Device Connected",
        ticketNo: "",
        deviceAvatar: "sap-icon://responsive",
        lastQuestionTime: Date.now(),
        demoMode: true,
        demoStep: 0,
        lastDemoSentenceTime: Date.now(),
        chats:[],
      };
    }

    componentDidMount() {
      if (this.props.runDemo) {
        this.timer = setInterval(this.tickDemo.bind(this), randomMessageInterval);
      }
      //this.scrollToBottom();
      this.props.chatListener && this.props.chatListener(chatEvent.ADD_CHAT, this.props.chatListenerIdentifier,  {functionAdd: this.addChat.bind(this), functionInfo: this.getInfo.bind(this)});
    }
  
    componentDidUpdate() {
      //this.scrollToBottom();
    }

    componentWillUnmount() {
      if (this.timer) {
        clearInterval(this.timer);
      }
    }

    /*
    scrollToBottom = () => {
      var that = this;
      setTimeout(function() {
        that.chatsEnd.scrollIntoView({ behavior: "smooth" });
      }, 100);
    }
    */

    getInfo(updateInfo) {
      const regexWatch = /(watch)/i;
      const regexPhone = /(phone)/i;

      if (updateInfo) {
        if (updateInfo.reset) {
          var ch = this.state.chats;
          ch.length = 0;
          this.setState({
            chatIsFree: true,
            deviceId: DEVICE_ID_NOT_SET,
            deviceName: "No Device Connected",
            ticketNo: "",
            deviceAvatar: "sap-icon://responsive",
            lastQuestionTime: Date.now(),
            chats: ch
          });
        } else {
          var deviceIcon = "sap-icon://responsive";
          var regex = regexWatch.exec(updateInfo.deviceId);
          if (regex) {
            deviceIcon = "sap-icon://history";
          }
          regex = regexPhone.exec(updateInfo.deviceId);
          if (regex) {
            deviceIcon = "sap-icon://call";
          }
  
          this.setState({
            chatIsFree: updateInfo.chatIsFree,
            deviceId: updateInfo.deviceId,
            deviceName: updateInfo.deviceId,
            ticketNo: updateInfo.ticketNo,
            deviceAvatar: deviceIcon
          });
        }
      }
      return {
        chatIsFree: this.state.chatIsFree,
        deviceId: this.state.deviceId,
        chatListenerIdentifier: this.props.chatListenerIdentifier,
      };
    }

    addChat(entry, fromDemo = false) {
      var id = Math.random();
      var icon = entry.bot ? 'sap-icon://headset' : 'sap-icon://person-placeholder';
      var type = entry.bot ? 'userBot' : 'userPerson';
      var chat = {
       id: "id-"+id,
       titleText: undefined,
       timestamp: entry.time,
       icon: icon,
       itemName: entry.user,
       content: entry.message,
       type: type
      }
      var ch = this.state.chats;
      if (this.state.demoMode && !fromDemo) {
        // Switch from Demo to real chat
        ch.length = 0;
      }
      ch.push(chat);
      this.setState({chats: []}); // Workaround - otherwise not all lines on the left side of timeline will be shown in timeline
      this.setState({chats: ch});
      this.setState({demoMode: fromDemo});
      if (!fromDemo) {
        this.setState({lastQuestionTime: Date.now()});
        this.setState({demoStep: 0});
        if (entry.done && entry.done === true) {
          this.clearChat();
        }
      }
    }

    clearChat() {
      var that = this;
      setTimeout(function() {
        var ch = that.state.chats;
        ch.length = 0;
        that.setState({chats: ch, demoMode: true, chatIsFree: true});
        that.props.chatListener(chatEvent.CLOSE_CHAT, that.props.chatListenerIdentifier);
      }, 5000);
    }

    tickDemo() {
      if (!this.state.demoMode) {
        return;
      }
      if (demo[this.state.demoStep] === demoStep.END) {
        if (this.state.lastDemoSentenceTime+ 5*1000 > Date.now()) {
          return;
        }
        this.setState({demoStep: ((this.state.demoStep+1) >= demo.length ? 0 : this.state.demoStep + 1)});
        var ch = this.state.chats;
        ch.length = 0;
        this.setState({chats: ch});
        return;
      }
      
      if ((this.state.lastQuestionTime + randomMessageIntervalStart) > Date.now() ||
          this.state.lastDemoSentenceTime+ demoSteps[demo[this.state.demoStep]].showAfterSeconds*1000 > Date.now()) {
        return;
      }
      if(this.state.demoStep === 0) {
        this.setState({deviceName: "Volker's Watch", ticketNo: (100 + Math.floor(Math.random() * Math.floor(400))), deviceAvatar: "sap-icon://history"});
      }

      this.addChat( {
        bot: demoSteps[demo[this.state.demoStep]].bot,
        user: demoSteps[demo[this.state.demoStep]].bot ? 'Maintenance Assistant' : 'Volker',
        time: Date.now(),
        message: demoSteps[demo[this.state.demoStep]].message,
        done: false
      }, true);
      this.setState({demoStep: this.state.demoStep + 1, lastDemoSentenceTime: Date.now()});
    }

    render() {
      return (
          <ui5-card
          avatar={this.state.deviceAvatar}
          heading={this.state.deviceName}
          subtitle= {'Ticket Number: '.concat(this.state.ticketNo)}
          class="meidum deviceCard">
            <div className="scrollableAndShowBottom scrollableAndShowBottom1Row">
              { this.state.chats.length > 0 &&
                <ui5-timeline>
                {
                  this.state.chats.map((chat) => {
                    return (
                      <ChatItem
                          key={chat.id}
                          id={chat.id}
                          type={chat.type}
                          titleText={chat.titleText}
                          timestamp={chat.timestamp}
                          icon={chat.icon}
                          itemName={chat.itemName}
                          content={chat.content}
                          >
                      </ChatItem>
                    )
                  })
                }
                </ui5-timeline>
              }
              <div style={{ float:"left", clear: "both" }}
                  ref={(el) => { this.chatsEnd = el; }}>
              </div>
            </div>
          </ui5-card>
      );
    }
  }
  
  export default ChatList;
  