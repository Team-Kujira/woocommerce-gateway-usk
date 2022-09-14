import { coins, SigningStargateClient, GasPrice } from "@cosmjs/stargate";
import { Window as KeplrWindow } from "@keplr-wallet/types";
import { Decimal } from "@cosmjs/math";
import { registry, aminoTypes, tx } from "kujira.js";
import { useState } from "react";
import { render } from "react-dom";
import { TxRaw } from "cosmjs-types/cosmos/tx/v1beta1/tx";

declare global {
  interface Window extends KeplrWindow {}
}

const DENOM =
  "factory/kujira1qk00h5atutpsv900x202pxx42npjr9thg58dnqpa72f2p7m2luase444a7/uusk";

const CHAIN_INFO = {
  chainId: "kaiyo-1",
  chainName: "Kujira",
  rpc: "https://rpc.kaiyo.kujira.setten.io",
  rest: "https://lcd.kaiyo.kujira.setten.io",
  bip44: {
    coinType: 118,
  },
  bech32Config: {
    bech32PrefixAccAddr: "kujira",
    bech32PrefixAccPub: "kujira" + "pub",
    bech32PrefixValAddr: "kujira" + "valoper",
    bech32PrefixValPub: "kujira" + "valoperpub",
    bech32PrefixConsAddr: "kujira" + "valcons",
    bech32PrefixConsPub: "kujira" + "valconspub",
  },
  currencies: [
    {
      coinDenom: "KUJI",
      coinMinimalDenom: "ukuji",
      coinDecimals: 6,
      coinGeckoId: "kujira",
    },
    {
      coinDenom: "USK",
      coinMinimalDenom: DENOM,
      coinDecimals: 6,
      coinGeckoId: "usk",
    },
  ],
  feeCurrencies: [
    {
      coinDenom: "USK",
      coinMinimalDenom: DENOM,
      coinDecimals: 6,
      coinGeckoId: "usk",
    },
  ],
  stakeCurrency: {
    coinDenom: "KUJI",
    coinMinimalDenom: "ukuji",
    coinDecimals: 6,
    coinGeckoId: "kujira",
  },
  coinType: 118,
  gasPriceStep: {
    low: 0.0015,
    average: 0.002,
    high: 0.003,
  },
};
const encode = (bytes: Uint8Array): string =>
  // @ts-expect-error intellisense doesn't like this for some reason
  Buffer.from(bytes).toString("base64");

const Component: React.FC<{ to: string; amount: string }> = (props) => {
  const amount = parseFloat(props.amount);
  const recipient = props.to;
  const [signed, setSigned] = useState("");

  const submit = async (e) => {
    e.preventDefault();

    if (!window.keplr) {
      alert("Please install keplr extension");
    } else {
      await window.keplr.experimentalSuggestChain(CHAIN_INFO);

      await window.keplr.enable(CHAIN_INFO.chainId);
      const offlineSigner = window.keplr.getOfflineSigner(CHAIN_INFO.chainId);

      const accounts = await offlineSigner.getAccounts();

      const gasPrice = new GasPrice(
        Decimal.fromUserInput("0.00150", 18),
        DENOM
      );

      const client = await SigningStargateClient.connectWithSigner(
        "https://rpc.kaiyo.kujira.setten.io",
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

      const gasInt = Math.max(Math.floor(feeInt / 0.0015), 100000);
      const txRaw = await client.sign(
        accounts[0].address,
        [msg],
        {
          // This is ignored by Keplr for now
          amount: coins(feeInt, DENOM),

          gas: gasInt.toString(),
        },
        ""
      );

      const txBytes = TxRaw.encode(txRaw).finish();

      setSigned(encode(txBytes));
    }
  };
  return (
    <div className="kujira-usk-payment">
      <textarea value={signed} name="usk_tx" />
      <button onClick={submit}>Pay {props.amount} USK</button>
    </div>
  );
};

export default (el, dataset) => render(<Component {...dataset} />, el);
