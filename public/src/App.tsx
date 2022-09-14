import { coins, SigningStargateClient, GasPrice } from "@cosmjs/stargate";
import { Window as KeplrWindow } from "@keplr-wallet/types";
import { Decimal } from "@cosmjs/math";
import { registry, aminoTypes, tx } from "kujira.js";
import { useState } from "react";
import { render } from "react-dom";

declare global {
  interface Window extends KeplrWindow {}
}

const DENOM =
  "factory/kujira1qk00h5atutpsv900x202pxx42npjr9thg58dnqpa72f2p7m2luase444a7/uusk";

const encode = (bytes: Uint8Array): string =>
  // @ts-expect-error intellisense doesn't like this for some reason
  Buffer.from(bytes).toString("base64");

const Component: React.FC<{ to: string; amount: string }> = (props) => {
  const amount = parseFloat(props.amount);
  const recipient = props.to;
  const [auth, setAuth] = useState("");
  const [body, setBody] = useState("");
  const [signature, setSignature] = useState("");

  const submit = async (e) => {
    console.log("foo");
    e.preventDefault();

    if (!window.keplr) {
      alert("Please install keplr extension");
    } else {
      const chainId = "harpoon-4";
      await window.keplr.enable(chainId);
      const offlineSigner = window.keplr.getOfflineSigner(chainId);

      const accounts = await offlineSigner.getAccounts();

      const gasPrice = new GasPrice(
        Decimal.fromUserInput("0.00150", 18),
        DENOM
      );

      const client = await SigningStargateClient.connectWithSigner(
        "https://rpc.harpoon.kujira.setten.io",
        offlineSigner,
        {
          registry,
          gasPrice,
          aminoTypes: aminoTypes("kujira"),
        }
      );

      const amountInt = Math.floor(amount * 10 ** 6);
      const feeInt = Math.floor(amountInt * 0.01);

      const msg = tx.bank.msgSend({
        amount: coins(amountInt, DENOM),
        from_address: accounts[0].address,
        to_address: recipient,
      });

      const res = await client.sign(
        accounts[0].address,
        [msg],
        {
          amount: coins(feeInt, DENOM),
          gas: "200000",
        },
        ""
      );

      setAuth(encode(res.authInfoBytes));
      setBody(encode(res.bodyBytes));
      setSignature(encode(res.signatures[0]));
    }
  };
  return (
    <div>
      <input value={auth} type="text" name="usk_auth" />
      <input value={body} type="text" name="usk_body" />
      <input value={signature} type="text" name="usk_signature" />
      <button onClick={submit}>Pay {props.amount} USK</button>
    </div>
  );
};

export default (el, dataset) => render(<Component {...dataset} />, el);
