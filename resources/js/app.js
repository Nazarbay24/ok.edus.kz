require('./bootstrap');

const chat = Echo.join('chat.69275_433127');

chat.subscribed(() => {
    console.log('chat subscribed!!!');
}).listen('.new_message', (event) => {
    console.log(event);
})

const channels = Echo.join('channels.69275');

channels.subscribed(() => {
    console.log('channels subscribed!!!');
}).listen('.new_channel', (event) => {
    console.log(event);
})
