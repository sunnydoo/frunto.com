import React, { Component } from 'react';

class ChatItem extends Component {
    render() {
        return (
            <ui5-timeline-item class={this.props.type} id={this.props.id} title-text={this.props.titleText} timestamp={this.props.timestamp} icon={this.props.icon} item-name={this.props.itemName} item-name-clickable>
                {this.props.content && <div>{this.props.content}</div>}
            </ui5-timeline-item>
        )
      }
}

export default ChatItem;
