#!/usr/bin/env python

from __future__ import print_function
from future.standard_library import install_aliases
install_aliases()

from urllib.parse import urlparse, urlencode
from urllib.request import urlopen, Request
from urllib.error import HTTPError

import json
import os

from flask import Flask
from flask import request
from flask import make_response

# Flask app should start in global layout
app = Flask(__name__)


@app.route('/webhook', methods=['POST'])
def webhook():
    req = request.get_json(silent=True, force=True)

    print("Request:")
    print(json.dumps(req, indent=4))

    res = processRequest(req)

    res = json.dumps(res, indent=4)
    # print(res)
    r = make_response(res)
    r.headers['Content-Type'] = 'application/json'
    return r


def processRequest(req):
    if req.get("result").get("action") != "ChuckJokes":
        return {}
    baseurl = "https://api.icndb.com/jokes/random?escape=javascript"
    result = urlopen(baseurl).read()
    data = json.loads(result)
    res = makeWebhookResult(data)
    return res


def makeWebhookResult(data):
    rtype = data.get('type')
    if rtype is None:
        return {}

    rvalue = data.get('value')
    if rvalue is None:
        return {}

    vid = rvalue.get('id')
    joke = rvalue.get('joke')
    if (vid is None) or (joke is None):
        return {}

    joke = joke.translate(None, "\\")

    print("Response:")
    print(joke)

    return {
        "speech": joke,
        "displayText": joke,
        "source": "apiai-chuck-norris-jokes"
    }


if __name__ == '__main__':
    port = int(os.getenv('PORT', 5000))

    print("Starting app on port %d" % port)

    app.run(debug=False, port=port, host='0.0.0.0')
