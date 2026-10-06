#!/usr/bin/env python3
"""Convert a Celsius value supplied on the command line to Fahrenheit."""

import argparse
import math


def main() -> None:
    parser = argparse.ArgumentParser(description="Convert Celsius to Fahrenheit")
    parser.add_argument("celsius", type=float, help="temperature in degrees Celsius")
    args = parser.parse_args()
    if not math.isfinite(args.celsius):
        parser.error("temperature must be a finite number")
    fahrenheit = args.celsius * 9 / 5 + 32
    if not math.isfinite(fahrenheit):
        parser.error("converted temperature is not finite")
    print(f"{fahrenheit:g} °F")


if __name__ == "__main__":
    main()
